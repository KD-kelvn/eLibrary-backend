<?php

namespace Modules\Notifications\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Modules\Authentication\Models\User;
use Modules\Authorization\Models\Role;
use Modules\Authorization\Models\SystemAction;
use Modules\Authorization\Models\UserRole;
use Modules\Authorization\Models\UserSystemAction;
use Modules\Notifications\Enums\NotificationCategory;
use Modules\Notifications\Enums\NotificationChannel;
use Modules\Notifications\Models\NotificationPreference;
use Modules\Notifications\Notifications\LibraryAlertNotification;
use Modules\Notifications\Services\ChannelResolver;
use Modules\Notifications\Services\NotificationService;
use Tests\TestCase;

/**
 * V1-005 / TC-NOT-01 — preferences, in-app delivery, mandatory external fallback.
 */
class NotificationsCoreTest extends TestCase
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
            'notify_deliveries',
            'notify_preferences',
            'notify_notifications',
            'user_system_actions',
            'system_action_roles',
            'system_actions',
            'revoked_roles',
            'user_roles',
            'roles',
            'users',
            'setting_values',
            'provider_settings',
            'personal_access_tokens',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_mandatory_alert_keeps_in_app_and_email_when_sms_disabled(): void
    {
        [$user] = $this->seedMemberWithPrefsPermission();

        NotificationPreference::query()->create([
            'user_id' => $user->id,
            'category' => NotificationCategory::Overdue->value,
            'channel' => NotificationChannel::InApp->value,
            'enabled' => true,
        ]);
        NotificationPreference::query()->create([
            'user_id' => $user->id,
            'category' => NotificationCategory::Overdue->value,
            'channel' => NotificationChannel::Email->value,
            'enabled' => true,
        ]);
        NotificationPreference::query()->create([
            'user_id' => $user->id,
            'category' => NotificationCategory::Overdue->value,
            'channel' => NotificationChannel::Sms->value,
            'enabled' => false,
        ]);

        Notification::fake();

        app(NotificationService::class)->send(
            $user,
            NotificationCategory::Overdue,
            'Overdue loan',
            'Please return your book.',
        );

        Notification::assertSentTo($user, LibraryAlertNotification::class, function (LibraryAlertNotification $notification) {
            $drivers = $notification->via($notification);

            return in_array('database', $drivers, true)
                && in_array('mail', $drivers, true)
                && ! in_array('sms', $drivers, true)
                && ! in_array('log_sms', $drivers, true);
        });
    }

    public function test_disabling_all_external_still_forces_mandatory_external(): void
    {
        [$user] = $this->seedMemberWithPrefsPermission();

        foreach ([NotificationChannel::InApp, NotificationChannel::Email, NotificationChannel::Sms] as $channel) {
            NotificationPreference::query()->create([
                'user_id' => $user->id,
                'category' => NotificationCategory::Penalty->value,
                'channel' => $channel->value,
                'enabled' => $channel === NotificationChannel::InApp,
            ]);
        }

        $channels = app(ChannelResolver::class)
            ->for($user, NotificationCategory::Penalty);

        $values = array_map(fn ($channel) => $channel->value, $channels);
        $this->assertContains(NotificationChannel::InApp->value, $values);
        $this->assertTrue(
            in_array(NotificationChannel::Email->value, $values, true)
            || in_array(NotificationChannel::Sms->value, $values, true),
        );
    }

    public function test_list_mark_read_and_preferences_api(): void
    {
        [$user] = $this->seedMemberWithPrefsPermission();
        Sanctum::actingAs($user);

        app(NotificationService::class)->send(
            $user,
            NotificationCategory::DueSoon,
            'Due soon',
            'Your loan is due in 3 days.',
        );

        $this->assertDatabaseCount('notify_deliveries', 3);

        $list = $this->getJson('/api/notifications');
        $list->assertOk();
        $this->assertGreaterThanOrEqual(1, $list->json('data.meta.unread_count'));
        $id = $list->json('data.items.0.id');

        $this->postJson("/api/notifications/{$id}/read")->assertOk()
            ->assertJsonPath('data.readAt', fn ($value) => filled($value));

        $this->getJson('/api/notifications/preferences')->assertOk()
            ->assertJsonPath('data.items.0.category', fn ($value) => filled($value));

        $this->putJson('/api/notifications/preferences', [
            'preferences' => [
                [
                    'category' => NotificationCategory::DueSoon->value,
                    'channel' => NotificationChannel::Sms->value,
                    'enabled' => false,
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('notify_preferences', [
            'user_id' => $user->id,
            'category' => NotificationCategory::DueSoon->value,
            'channel' => NotificationChannel::Sms->value,
            'enabled' => 0,
        ]);
    }

    /**
     * @return array{0: User}
     */
    protected function seedMemberWithPrefsPermission(): array
    {
        $role = Role::query()->create([
            'name' => 'Student',
            'code' => 'student',
            'status' => 'active',
            'is_system' => true,
        ]);

        $action = SystemAction::query()->create([
            'name' => 'Notification preferences',
            'code' => 'notifications.preferences',
            'role_id' => $role->id,
            'action_type' => 'permission',
            'is_active' => true,
        ]);

        $user = User::query()->create([
            'username' => 'member-notify',
            'email' => 'member-notify@example.com',
            'password' => 'password',
            'is_blocked' => false,
            'is_active' => true,
        ]);

        $assignment = UserRole::query()->create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'assigned_by' => $user->id,
            'assigned_at' => now(),
        ]);

        UserSystemAction::query()->create([
            'user_id' => $user->id,
            'system_action_id' => $action->id,
            'user_role_id' => $assignment->id,
        ]);

        return [$user];
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

        Schema::create('provider_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 64)->unique();
            $table->text('config');
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }
}
