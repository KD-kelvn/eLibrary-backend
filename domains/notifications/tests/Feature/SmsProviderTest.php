<?php

namespace Modules\Notifications\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Modules\Authentication\Enums\OneTimeTokenPurpose;
use Modules\Authentication\Enums\OneTimeTokenVia;
use Modules\Authentication\Models\User;
use Modules\Authentication\Notifications\OneTimePasswordNotification;
use Modules\Notifications\Enums\NotificationCategory;
use Modules\Notifications\Enums\NotificationChannel;
use Modules\Notifications\Notifications\LibraryAlertNotification;
use Modules\Notifications\Services\NotificationService;
use Modules\Notifications\Services\QuietHoursGuard;
use Modules\Notifications\Sms\Drivers\BeemSmsProvider;
use Modules\Notifications\Sms\SmsProviderManager;
use Modules\Settings\Models\ProviderSetting;
use Modules\Settings\Models\SettingValue;
use Modules\Settings\Services\ProviderSettingsService;
use Modules\Settings\Support\SettingsCache;
use Tests\TestCase;

/**
 * V1-006 / TC-NOT-02 / TC-NOT-03 — SMS drivers, quiet hours, EN/SW templates.
 */
class SmsProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['audit.enabled' => false]);
        $this->createSchema();
    }

    protected function tearDown(): void
    {
        foreach ([
            'provider_settings',
            'notify_deliveries',
            'notify_preferences',
            'notify_notifications',
            'users',
            'setting_values',
            'activity_log',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_beem_send_matches_family_hub_contract(): void
    {
        Http::fake([
            'apisms.beem.africa/*' => Http::response([
                'successful' => true,
                'request_id' => 4321,
                'code' => 100,
                'message' => 'Message Submitted Successfully',
            ]),
        ]);

        $result = (new BeemSmsProvider([
            'url' => 'https://apisms.beem.africa/v1/send',
            'api_key' => 'key',
            'secret_key' => 'secret',
            'sender_id' => 'ELIB',
        ]))->send('+255700000003', 'Hello');

        $this->assertTrue($result->ok);
        $this->assertSame('beem', $result->provider);
        $this->assertSame('4321', $result->providerMessageId);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://apisms.beem.africa/v1/send'
                && $request->hasHeader('Authorization', 'Basic '.base64_encode('key:secret'))
                && $request['source_addr'] === 'ELIB'
                && $request['message'] === 'Hello'
                && $request['recipients'] === [['recipient_id' => '1', 'dest_addr' => '255700000003']];
        });
    }

    public function test_quiet_hours_skip_library_sms_but_otp_still_sends(): void
    {
        $this->travelTo(now()->setTime(22, 30));

        ProviderSetting::query()->create([
            'group' => 'sms',
            'config' => ['driver' => 'log'],
        ]);

        // Force quiet hours on via settings store default path: seed setting_values
        SettingValue::query()->create([
            'key' => 'sms.quiet_hours',
            'scope' => 'global',
            'value' => ['v' => ['start' => '21:00', 'end' => '07:00', 'enabled' => true]],
            'effective_from' => now()->subDay(),
            'reason' => 'test',
        ]);
        app(SettingsCache::class)->bump();

        $this->assertTrue(app(QuietHoursGuard::class)->isQuiet());

        $user = User::query()->create([
            'username' => 'sms-member',
            'email' => 'sms-member@example.com',
            'phone' => '+255700000001',
            'password' => 'password',
            'is_blocked' => false,
            'is_active' => true,
            'locale' => 'en',
        ]);

        Notification::fake();

        app(NotificationService::class)->send(
            $user,
            NotificationCategory::Overdue,
            'Overdue',
            'Return the book.',
        );

        Notification::assertSentTo($user, LibraryAlertNotification::class, function (LibraryAlertNotification $n) {
            return ! in_array('sms', $n->via($n), true);
        });

        $this->assertDatabaseHas('notify_deliveries', [
            'user_id' => $user->id,
            'channel' => NotificationChannel::Sms->value,
            'status' => 'skipped',
            'error' => 'skipped_quiet_hours',
        ]);

        // OTP bypasses quiet hours — SmsChannel still delivers.
        $otp = new OneTimePasswordNotification('123456', OneTimeTokenVia::Phone, 10, OneTimeTokenPurpose::Login);
        $this->assertSame(['sms'], $otp->via($user));
        $user->notify($otp);
        Notification::assertSentTo($user, OneTimePasswordNotification::class);
    }

    public function test_swahili_otp_sms_template(): void
    {
        $user = User::query()->create([
            'username' => 'sw-member',
            'email' => 'sw-member@example.com',
            'phone' => '+255700000002',
            'password' => 'password',
            'is_blocked' => false,
            'is_active' => true,
            'locale' => 'sw',
        ]);

        $otp = new OneTimePasswordNotification('654321', OneTimeTokenVia::Phone, 5, OneTimeTokenPurpose::Login);
        $text = $otp->toSms($user);

        $this->assertStringContainsString('654321', $text);
        $this->assertStringContainsString('Nambari yako ya kuingia', $text);
        $this->assertLessThanOrEqual(160, mb_strlen($text));
    }

    public function test_provider_settings_redacts_secrets(): void
    {
        $service = app(ProviderSettingsService::class);
        $service->updateSms([
            'driver' => 'beem',
            'beem' => [
                'api_key' => 'live-key',
                'secret_key' => 'live-secret',
                'sender_id' => 'ELIB',
            ],
        ], null);

        $view = $service->publicView();
        $this->assertSame('beem', $view['sms']['driver']);
        $this->assertTrue($view['sms']['beem']['api_key_set']);
        $this->assertTrue($view['sms']['beem']['secret_key_set']);
        $this->assertSame('ELIB', $view['sms']['beem']['sender_id']);
        $this->assertArrayNotHasKey('api_key', $view['sms']['beem']);
        $this->assertArrayNotHasKey('secret_key', $view['sms']['beem']);

        // Blank secret keeps previous value.
        $service->updateSms([
            'beem' => ['api_key' => '', 'sender_id' => 'ELIB2'],
        ], null);
        $config = $service->smsConfig();
        $this->assertSame('live-key', $config['beem']['api_key']);
        $this->assertSame('ELIB2', $config['beem']['sender_id']);
        $this->assertSame('beem', app(SmsProviderManager::class)->activeName());
    }

    protected function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('password');
            $table->boolean('is_blocked')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestampTz('locked_until')->nullable();
            $table->string('locale', 10)->default('en');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('notify_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notify_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('category', 64);
            $table->string('channel', 32);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->unique(['user_id', 'category', 'channel']);
        });

        Schema::create('notify_deliveries', function (Blueprint $table) {
            $table->id();
            $table->uuid('notification_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('category', 64);
            $table->string('channel', 32);
            $table->string('provider', 64)->nullable();
            $table->string('provider_message_id')->nullable();
            $table->string('status', 32)->default('queued');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('error')->nullable();
            $table->timestamps();
        });

        Schema::create('provider_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 64)->unique();
            $table->text('config');
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('setting_values', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('scope')->default('global');
            $table->json('value')->nullable();
            $table->timestamp('effective_from');
            $table->foreignId('created_by')->nullable();
            $table->string('reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableMorphs('subject', 'subject');
            $table->nullableMorphs('causer', 'causer');
            $table->string('event')->nullable();
            $table->json('properties')->nullable();
            $table->json('attribute_changes')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->timestamps();
            $table->index('log_name');
        });
    }
}
