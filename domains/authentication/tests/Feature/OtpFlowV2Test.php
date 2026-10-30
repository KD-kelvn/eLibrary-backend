<?php

namespace Modules\Authentication\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Modules\Authentication\Enums\OneTimeTokenPurpose;
use Modules\Authentication\Exceptions\AuthenticationException;
use Modules\Authentication\Models\OneTimeToken;
use Modules\Authentication\Models\User;
use Modules\Authentication\Notifications\OneTimePasswordNotification;
use Modules\Authentication\Services\OneTimeTokenLoginService;
use Tests\TestCase;

/**
 * TC-AUTH-01..05/08 — OTP flow v2 (V1-011 / FR-AUTH-01..05/08).
 */
class OtpFlowV2Test extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'audit.enabled' => false,
            'authentication.otp.expose_in_response' => true,
        ]);
        Cache::flush();
        RateLimiter::clear('otp-send-id:student@example.com');
        RateLimiter::clear('otp-send-id:ghost@example.com');
        RateLimiter::clear('otp-send-id:inactive@example.com');
        Notification::fake();
        $this->createSchema();
    }

    protected function tearDown(): void
    {
        foreach ([
            'personal_access_tokens',
            'one_time_tokens',
            'user_system_actions',
            'system_action_roles',
            'system_actions',
            'user_profiles',
            'revoked_roles',
            'user_roles',
            'roles',
            'setting_values',
            'activity_log',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        parent::tearDown();
    }

    public function test_options_for_phone_email_and_username(): void
    {
        $this->seedMember();

        foreach (['student@example.com', '0712345678', 'BIT2021001'] as $identifier) {
            $response = $this->postJson('/api/auth/otp/options', ['identifier' => $identifier]);
            $response->assertOk();
            $response->assertJsonPath('message', OneTimeTokenLoginService::GENERIC_CONTINUE);
            $this->assertNotEmpty($response->json('data.challenge_id'));
            $types = collect($response->json('data.channels'))->pluck('type')->all();
            $this->assertSame(['sms', 'email'], $types);
            $this->assertStringContainsString('*', $response->json('data.channels.0.masked'));
        }
    }

    public function test_channel_choice_sends_on_chosen_channel(): void
    {
        $user = $this->seedMember();

        $options = $this->postJson('/api/auth/otp/options', ['identifier' => $user->email])->json('data');
        $request = $this->postJson('/api/auth/otp/request', [
            'challenge_id' => $options['challenge_id'],
            'channel' => 'email',
        ]);

        $request->assertOk();
        $request->assertJsonPath('message', OneTimeTokenLoginService::GENERIC_SENT);
        $request->assertJsonPath('data.channel', 'email');
        $this->assertNotEmpty($request->json('data.code'));

        Notification::assertSentTo($user, OneTimePasswordNotification::class);

        $token = OneTimeToken::query()->where('user_id', $user->id)->first();
        $this->assertSame($options['challenge_id'], $token->challenge_id);
        $this->assertSame('email', $token->via);
        $this->assertTrue(Hash::check($request->json('data.code'), $token->token));
    }

    public function test_expired_reused_and_superseded_codes_rejected(): void
    {
        $user = $this->seedMember();
        $otp = app(OneTimeTokenLoginService::class);

        $challenge = $otp->options($user->email);
        $first = $otp->requestForChallenge([
            'challenge_id' => $challenge['challenge_id'],
            'channel' => 'email',
        ]);
        $firstCode = $first['code'];

        $second = $otp->requestForChallenge([
            'challenge_id' => $challenge['challenge_id'],
            'channel' => 'email',
        ]);
        $secondCode = $second['code'];

        try {
            $otp->verifyForChallenge([
                'challenge_id' => $challenge['challenge_id'],
                'code' => $firstCode,
                'device_name' => 'test',
            ]);
            $this->fail('Superseded code should fail');
        } catch (AuthenticationException $e) {
            $this->assertSame(401, $e->getCode());
        }

        $auth = $otp->verifyForChallenge([
            'challenge_id' => $challenge['challenge_id'],
            'code' => $secondCode,
            'device_name' => 'test',
        ]);
        $this->assertArrayHasKey('access_token', $auth);

        try {
            $otp->verifyForChallenge([
                'challenge_id' => $challenge['challenge_id'],
                'code' => $secondCode,
                'device_name' => 'test',
            ]);
            $this->fail('Reused code should fail');
        } catch (AuthenticationException $e) {
            $this->assertSame(401, $e->getCode());
        }

        $challenge2 = $otp->options($user->email);
        $issued = $otp->requestForChallenge([
            'challenge_id' => $challenge2['challenge_id'],
            'channel' => 'sms',
        ]);
        OneTimeToken::query()->where('challenge_id', $challenge2['challenge_id'])->update([
            'expires_at' => now()->subMinute(),
        ]);

        try {
            $otp->verifyForChallenge([
                'challenge_id' => $challenge2['challenge_id'],
                'code' => $issued['code'],
            ]);
            $this->fail('Expired code should fail');
        } catch (AuthenticationException $e) {
            $this->assertSame(401, $e->getCode());
        }
    }

    public function test_fourth_send_is_rate_limited_and_wrong_codes_lock_account(): void
    {
        $user = $this->seedMember();
        $otp = app(OneTimeTokenLoginService::class);

        for ($i = 0; $i < 3; $i++) {
            $challenge = $otp->options($user->email);
            $otp->requestForChallenge([
                'challenge_id' => $challenge['challenge_id'],
                'channel' => 'email',
                'ip_address' => '127.0.0.1',
            ]);
        }

        $challenge = $otp->options($user->email);
        try {
            $otp->requestForChallenge([
                'challenge_id' => $challenge['challenge_id'],
                'channel' => 'email',
                'ip_address' => '127.0.0.1',
            ]);
            $this->fail('4th send should 429');
        } catch (AuthenticationException $e) {
            $this->assertSame(429, $e->getCode());
        }

        Cache::flush();
        RateLimiter::clear('otp-send-id:'.strtolower($user->email));

        $challenge = $otp->options($user->email);
        $otp->requestForChallenge([
            'challenge_id' => $challenge['challenge_id'],
            'channel' => 'email',
            'ip_address' => '10.0.0.2',
        ]);

        for ($i = 0; $i < 5; $i++) {
            try {
                $otp->verifyForChallenge([
                    'challenge_id' => $challenge['challenge_id'],
                    'code' => '000000',
                ]);
            } catch (AuthenticationException) {
                // expected
            }
        }

        $user->refresh();
        $this->assertTrue($user->isLocked());
        $this->assertFalse($user->canAuthenticate());
    }

    public function test_unknown_identifier_matches_known_response_shape(): void
    {
        $this->seedMember();

        $known = $this->postJson('/api/auth/otp/options', ['identifier' => 'student@example.com']);
        $unknown = $this->postJson('/api/auth/otp/options', ['identifier' => 'ghost@example.com']);

        $known->assertOk();
        $unknown->assertOk();
        $this->assertSame($known->json('message'), $unknown->json('message'));
        $this->assertSame(
            array_keys($known->json('data')),
            array_keys($unknown->json('data')),
        );
        $this->assertNotEmpty($unknown->json('data.challenge_id'));
        $this->assertSame([], $unknown->json('data.channels'));

        $request = $this->postJson('/api/auth/otp/request', [
            'challenge_id' => $unknown->json('data.challenge_id'),
            'channel' => 'email',
        ]);
        $request->assertOk();
        $request->assertJsonPath('message', OneTimeTokenLoginService::GENERIC_SENT);
        Notification::assertNothingSent();
    }

    public function test_inactive_user_gets_generic_response_without_otp(): void
    {
        User::query()->create([
            'username' => 'INACTIVE1',
            'email' => 'inactive@example.com',
            'phone' => '+255700000001',
            'password' => 'password',
            'is_blocked' => false,
            'is_active' => false,
            'locale' => 'en',
        ]);

        $options = $this->postJson('/api/auth/otp/options', ['identifier' => 'inactive@example.com']);
        $options->assertOk();
        $this->assertSame([], $options->json('data.channels'));

        $request = $this->postJson('/api/auth/otp/request', [
            'challenge_id' => $options->json('data.challenge_id'),
            'channel' => 'email',
        ]);
        $request->assertOk();
        Notification::assertNothingSent();
        $this->assertSame(0, OneTimeToken::query()->count());
    }

    public function test_http_options_request_then_verify_issues_token(): void
    {
        $user = $this->seedMember();

        $options = $this->postJson('/api/auth/otp/options', ['identifier' => $user->username]);
        $options->assertOk();
        $challengeId = $options->json('data.challenge_id');

        $sent = $this->postJson('/api/auth/otp/request', [
            'challenge_id' => $challengeId,
            'channel' => 'sms',
        ]);
        $sent->assertOk();
        $this->assertNotEmpty($sent->json('data.code'));

        // AuthResource pulls RBAC/modules tables; assert token issue at the service boundary.
        $auth = app(OneTimeTokenLoginService::class)->verifyForChallenge([
            'challenge_id' => $challengeId,
            'code' => $sent->json('data.code'),
            'device_name' => 'phpunit',
        ]);

        $this->assertNotEmpty($auth['access_token']);
        $this->assertNotNull(
            OneTimeToken::query()
                ->where('purpose', OneTimeTokenPurpose::Login->value)
                ->whereNotNull('used_at')
                ->first()
        );
    }

    protected function seedMember(): User
    {
        return User::query()->create([
            'username' => 'BIT2021001',
            'email' => 'student@example.com',
            'phone' => '+255712345678',
            'password' => 'password',
            'is_blocked' => false,
            'is_active' => true,
            'locale' => 'en',
        ]);
    }

    protected function createSchema(): void
    {
        Schema::create('setting_values', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('scope');
            $table->json('value');
            $table->timestamp('effective_from');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->nullable()->index();
            $table->text('description');
            $table->nullableMorphs('subject', 'subject');
            $table->string('event')->nullable();
            $table->nullableMorphs('causer', 'causer');
            $table->json('attribute_changes')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('phone')->nullable()->unique();
            $table->string('password');
            $table->boolean('is_blocked')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('deactivated_reason')->nullable();
            $table->boolean('break_glass')->default(false);
            $table->timestamp('locked_until')->nullable();
            $table->string('locale', 10)->default('en');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('fullname')->nullable();
            $table->string('gender')->nullable();
            $table->date('dob')->nullable();
            $table->string('profile_picture')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('status')->default('active');
            $table->boolean('is_system')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('role_id');
            $table->foreignId('assigned_by')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('revoked_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_role_id');
            $table->foreignId('revoked_by')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('system_actions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->foreignId('role_id')->nullable();
            $table->string('action_type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('system_action_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_action_id');
            $table->foreignId('role_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('user_system_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('system_action_id');
            $table->foreignId('user_role_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('one_time_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->uuid('challenge_id')->nullable()->index();
            $table->string('token');
            $table->string('via');
            $table->timestamp('expires_at')->nullable();
            $table->string('purpose');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('max_attempts')->nullable();
            $table->unsignedTinyInteger('code_length')->nullable();
            $table->unsignedTinyInteger('expiry_minutes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }
}
