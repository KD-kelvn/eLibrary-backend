<?php

namespace Modules\Settings\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Modules\Authentication\Models\User;
use Modules\Authorization\Models\Role;
use Modules\Authorization\Models\SystemAction;
use Modules\Authorization\Models\UserRole;
use Modules\Authorization\Models\UserSystemAction;
use Tests\TestCase;

/**
 * V1-006 — GET/PUT /api/settings/providers (secrets write-only).
 */
class ProviderSettingsApiTest extends TestCase
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
            'user_system_actions',
            'system_action_roles',
            'system_actions',
            'revoked_roles',
            'user_roles',
            'roles',
            'users',
            'personal_access_tokens',
            'activity_log',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_admin_can_read_and_update_sms_providers_without_leaking_secrets(): void
    {
        [$admin] = $this->seedAdminWithProvidersPermission();
        Sanctum::actingAs($admin);

        $this->getJson('/api/settings/providers')
            ->assertOk()
            ->assertJsonPath('data.sms.driver', 'log')
            ->assertJsonMissingPath('data.sms.beem.api_key');

        $this->putJson('/api/settings/providers', [
            'sms' => [
                'driver' => 'beem',
                'beem' => [
                    'api_key' => 'secret-key-value',
                    'secret_key' => 'secret-secret-value',
                    'sender_id' => 'ELIB',
                ],
            ],
        ])->assertOk()
            ->assertJsonPath('data.sms.driver', 'beem')
            ->assertJsonPath('data.sms.beem.sender_id', 'ELIB')
            ->assertJsonPath('data.sms.beem.api_key_set', true)
            ->assertJsonMissingPath('data.sms.beem.api_key');
    }

    public function test_member_without_permission_gets_403(): void
    {
        $user = User::query()->create([
            'username' => 'member-providers',
            'email' => 'member-providers@example.com',
            'password' => 'password',
            'is_blocked' => false,
            'is_active' => true,
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/settings/providers')->assertForbidden();
    }

    /**
     * @return array{0: User}
     */
    protected function seedAdminWithProvidersPermission(): array
    {
        $role = Role::query()->create([
            'name' => 'Admin',
            'code' => 'admin',
            'status' => 'active',
            'is_system' => true,
        ]);

        $action = SystemAction::query()->create([
            'name' => 'Provider settings',
            'code' => 'settings.providers',
            'role_id' => $role->id,
            'action_type' => 'permission',
            'is_active' => true,
        ]);

        $user = User::query()->create([
            'username' => 'admin-providers',
            'email' => 'admin-providers@example.com',
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
