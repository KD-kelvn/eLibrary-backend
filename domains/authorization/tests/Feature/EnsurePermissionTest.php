<?php

namespace Modules\Authorization\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Modules\Authentication\Models\User;
use Modules\Authorization\Models\Role;
use Modules\Authorization\Models\SystemAction;
use Modules\Authorization\Models\SystemActionRole;
use Modules\Authorization\Models\UserRole;
use Modules\Authorization\Models\UserSystemAction;
use Modules\Authorization\Services\PermissionChecker;
use Tests\TestCase;

/**
 * Happy / 403 coverage for permission middleware on library settings (V1-004).
 */
class EnsurePermissionTest extends TestCase
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
            'user_system_actions',
            'system_action_roles',
            'system_actions',
            'revoked_roles',
            'user_roles',
            'roles',
            'users',
            'setting_values',
            'personal_access_tokens',
            'activity_log',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        parent::tearDown();
    }

    public function test_library_settings_get_requires_view_grant(): void
    {
        [$admin, $member] = $this->seedUsersWithSettingsActions();

        Sanctum::actingAs($member);
        $this->getJson('/api/settings/library')
            ->assertForbidden();

        Sanctum::actingAs($admin);
        $this->getJson('/api/settings/library')
            ->assertOk();
    }

    public function test_library_settings_put_requires_update_grant(): void
    {
        [$admin, $librarian] = $this->seedUsersWithSettingsActions(includeLibrarianView: true);

        $checker = app(PermissionChecker::class);
        $this->assertTrue($checker->allowsOne($admin, 'settings.library.update'));
        $this->assertFalse($checker->allowsOne($librarian, 'settings.library.update'));

        Sanctum::actingAs($librarian);
        $this->putJson('/api/settings/library', [
            'reason' => 'Attempted change without update grant xx',
            'changes' => [
                ['key' => 'fines.grace_days', 'scope' => 'role:student', 'value' => 2],
            ],
        ])->assertForbidden();
    }

    /**
     * @return array{0: User, 1: User}
     */
    protected function seedUsersWithSettingsActions(bool $includeLibrarianView = false): array
    {
        $adminRole = Role::query()->create([
            'name' => 'System Admin',
            'code' => 'admin',
            'status' => 'active',
            'is_system' => true,
        ]);
        $libRole = Role::query()->create([
            'name' => 'Librarian',
            'code' => 'librarian',
            'status' => 'active',
            'is_system' => true,
        ]);
        $studentRole = Role::query()->create([
            'name' => 'Student',
            'code' => 'student',
            'status' => 'active',
            'is_system' => true,
        ]);

        $view = SystemAction::query()->create([
            'name' => 'View library settings',
            'code' => 'settings.library.view',
            'role_id' => $libRole->id,
            'action_type' => 'permission',
            'is_active' => true,
        ]);
        $update = SystemAction::query()->create([
            'name' => 'Update library settings',
            'code' => 'settings.library.update',
            'role_id' => $adminRole->id,
            'action_type' => 'permission',
            'is_active' => true,
        ]);
        $reset = SystemAction::query()->create([
            'name' => 'Reset library settings',
            'code' => 'settings.library.reset',
            'role_id' => $adminRole->id,
            'action_type' => 'permission',
            'is_active' => true,
        ]);

        foreach ([$libRole->id, $adminRole->id] as $roleId) {
            SystemActionRole::query()->create([
                'system_action_id' => $view->id,
                'role_id' => $roleId,
            ]);
        }
        SystemActionRole::query()->create([
            'system_action_id' => $update->id,
            'role_id' => $adminRole->id,
        ]);
        SystemActionRole::query()->create([
            'system_action_id' => $reset->id,
            'role_id' => $adminRole->id,
        ]);

        $admin = User::query()->create([
            'username' => 'admin-perm',
            'email' => 'admin-perm@example.com',
            'password' => 'password',
            'is_blocked' => false,
        ]);
        $other = User::query()->create([
            'username' => $includeLibrarianView ? 'lib-perm' : 'member-perm',
            'email' => ($includeLibrarianView ? 'lib' : 'member').'-perm@example.com',
            'password' => 'password',
            'is_blocked' => false,
        ]);

        $adminAssignment = UserRole::query()->create([
            'user_id' => $admin->id,
            'role_id' => $adminRole->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
        ]);
        $otherRole = $includeLibrarianView ? $libRole : $studentRole;
        $otherAssignment = UserRole::query()->create([
            'user_id' => $other->id,
            'role_id' => $otherRole->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
        ]);

        foreach ([$view, $update, $reset] as $action) {
            UserSystemAction::query()->create([
                'user_id' => $admin->id,
                'system_action_id' => $action->id,
                'user_role_id' => $adminAssignment->id,
            ]);
        }

        if ($includeLibrarianView) {
            UserSystemAction::query()->create([
                'user_id' => $other->id,
                'system_action_id' => $view->id,
                'user_role_id' => $otherAssignment->id,
            ]);
        }

        return [$admin, $other];
    }

    protected function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_blocked')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestampTz('locked_until')->nullable();
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
            $table->foreignId('menu_item_id')->nullable();
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
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
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
