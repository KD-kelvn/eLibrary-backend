<?php

namespace Modules\Authentication\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Modules\Authentication\Exceptions\AuthenticationException;
use Modules\Authentication\Models\User;
use Modules\Authentication\Notifications\BreakGlassUsedNotification;
use Modules\Authentication\Services\BreakGlassLoginService;
use Modules\Authentication\Services\OneTimeTokenLoginService;
use Modules\Authorization\Models\Role;
use Modules\Authorization\Models\UserRole;
use Tests\TestCase;

/**
 * TC-AUTH-06 / TC-AUTH-07 — V1-012 break-glass + removed password routes.
 */
class BreakGlassAuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'audit.enabled' => false,
            'authentication.otp.expose_in_response' => true,
        ]);
        Cache::flush();
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
            'notifications',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        parent::tearDown();
    }

    public function test_removed_password_routes_return_not_found(): void
    {
        $this->postJson('/api/auth/register', [])->assertNotFound();
        $this->postJson('/api/auth/login', [])->assertNotFound();
        $this->postJson('/api/auth/password/forgot', [])->assertNotFound();
        $this->postJson('/api/auth/password/verify-otp', [])->assertNotFound();
        $this->postJson('/api/auth/password/reset', [])->assertNotFound();
    }

    public function test_break_glass_denied_without_flag_or_wrong_password(): void
    {
        [$adminRole] = $this->seedRoles();
        $admin = $this->seedUser('admin1', 'admin@example.com', true, false);
        $this->assignRole($admin, $adminRole);

        $this->postJson('/api/auth/break-glass', [
            'username' => 'admin1',
            'password' => 'password',
        ])->assertStatus(401);

        $flagged = $this->seedUser('glass1', 'glass@example.com', true, true);
        $this->assignRole($flagged, $adminRole);

        $this->postJson('/api/auth/break-glass', [
            'username' => 'glass1',
            'password' => 'wrong-password',
        ])->assertStatus(401);

        $memberRole = Role::query()->where('code', 'student')->first();
        $flaggedMember = $this->seedUser('glass2', 'glass2@example.com', true, true);
        $this->assignRole($flaggedMember, $memberRole);

        $this->postJson('/api/auth/break-glass', [
            'username' => 'glass2',
            'password' => 'password',
        ])->assertStatus(401);
    }

    public function test_break_glass_success_requires_otp_then_audits_and_notifies(): void
    {
        [$adminRole] = $this->seedRoles();
        $peer = $this->seedUser('adminpeer', 'peer@example.com', true, false);
        $this->assignRole($peer, $adminRole);

        $actor = $this->seedUser('glassadmin', 'glassadmin@example.com', true, true);
        $this->assignRole($actor, $adminRole);

        $start = $this->postJson('/api/auth/break-glass', [
            'username' => 'glassadmin',
            'password' => 'password',
        ]);
        $start->assertOk();
        $challengeId = $start->json('data.challenge_id');
        $this->assertNotEmpty($challengeId);
        $this->assertNotEmpty($start->json('data.channels'));

        // Password alone must not issue a token.
        $this->assertDatabaseCount('personal_access_tokens', 0);

        $sent = $this->postJson('/api/auth/otp/request', [
            'challenge_id' => $challengeId,
            'channel' => 'email',
        ]);
        $sent->assertOk();

        $auth = app(OneTimeTokenLoginService::class)->verifyForChallenge([
            'challenge_id' => $challengeId,
            'code' => $sent->json('data.code'),
            'device_name' => 'phpunit',
        ]);

        $this->assertNotEmpty($auth['access_token']);
        Notification::assertSentTo($peer, BreakGlassUsedNotification::class);
        Notification::assertNotSentTo($actor, BreakGlassUsedNotification::class);
    }

    public function test_break_glass_service_rejects_non_admin_flag(): void
    {
        $this->seedRoles();
        $user = $this->seedUser('plain', 'plain@example.com', true, true);

        $this->expectException(AuthenticationException::class);
        app(BreakGlassLoginService::class)->start('plain', 'password');
    }

    protected function seedRoles(): array
    {
        $admin = Role::query()->create([
            'name' => 'Admin',
            'code' => 'admin',
            'status' => 'active',
            'is_system' => true,
        ]);
        $student = Role::query()->create([
            'name' => 'Member',
            'code' => 'student',
            'status' => 'active',
            'is_system' => true,
        ]);

        return [$admin, $student];
    }

    protected function seedUser(string $username, string $email, bool $active, bool $breakGlass): User
    {
        return User::query()->create([
            'username' => $username,
            'email' => $email,
            'phone' => null,
            'password' => Hash::make('password'),
            'is_blocked' => false,
            'is_active' => $active,
            'break_glass' => $breakGlass,
            'locale' => 'en',
        ]);
    }

    protected function assignRole(User $user, Role $role): void
    {
        UserRole::query()->create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'assigned_at' => now(),
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
            $table->timestamps();
        });

        Schema::create('user_system_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('system_action_id');
            $table->foreignId('user_role_id')->nullable();
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

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }
}
