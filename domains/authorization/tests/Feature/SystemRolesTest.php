<?php

namespace Modules\Authorization\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Authentication\Models\User;
use Modules\Authorization\Exceptions\AuthorizationException;
use Modules\Authorization\Models\Role;
use Modules\Authorization\Models\UserRole;
use Modules\Authorization\Services\LastAdminGuard;
use Modules\Authorization\Services\RolesManagement\RolesService;
use Tests\TestCase;

/**
 * Service-layer coverage for TC-RBAC-01 / TC-RBAC-03 (V1-003).
 */
class SystemRolesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['audit.enabled' => false]);
        $this->createAuthSchema();
    }

    protected function tearDown(): void
    {
        foreach (['revoked_roles', 'user_roles', 'roles', 'users', 'activity_log'] as $table) {
            Schema::dropIfExists($table);
        }
        parent::tearDown();
    }

    public function test_system_role_cannot_be_renamed_or_deleted(): void
    {
        $admin = Role::query()->create([
            'name' => 'System Admin',
            'code' => 'admin',
            'status' => 'active',
            'is_system' => true,
        ]);

        $roles = app(RolesService::class);

        try {
            $roles->update($admin->id, ['name' => 'Renamed']);
            $this->fail('Expected SYSTEM_ROLE on rename');
        } catch (AuthorizationException $e) {
            $this->assertSame(422, $e->getCode());
            $this->assertSame('SYSTEM_ROLE', $e->errorCode());
        }

        try {
            $roles->destroy($admin->id);
            $this->fail('Expected SYSTEM_ROLE on delete');
        } catch (AuthorizationException $e) {
            $this->assertSame('SYSTEM_ROLE', $e->errorCode());
        }
    }

    public function test_last_admin_cannot_lose_admin_access(): void
    {
        $adminRole = Role::query()->create([
            'name' => 'System Admin',
            'code' => 'admin',
            'status' => 'active',
            'is_system' => true,
        ]);

        $user = User::query()->create([
            'username' => 'sole-admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'is_blocked' => false,
        ]);

        $assignment = UserRole::query()->create([
            'user_id' => $user->id,
            'role_id' => $adminRole->id,
            'assigned_by' => $user->id,
            'assigned_at' => now(),
        ]);

        $guard = app(LastAdminGuard::class);
        $this->assertSame(1, $guard->activeAdminUserCount());

        try {
            $guard->assertCanRevokeAdminAssignment($assignment);
            $this->fail('Expected LAST_ADMIN on revoke');
        } catch (AuthorizationException $e) {
            $this->assertSame('LAST_ADMIN', $e->errorCode());
        }

        try {
            $guard->assertCanBlockOrDeactivate($user);
            $this->fail('Expected LAST_ADMIN on block');
        } catch (AuthorizationException $e) {
            $this->assertSame('LAST_ADMIN', $e->errorCode());
        }
    }

    public function test_second_admin_can_be_blocked(): void
    {
        $adminRole = Role::query()->create([
            'name' => 'System Admin',
            'code' => 'admin',
            'status' => 'active',
            'is_system' => true,
        ]);

        $a = User::query()->create([
            'username' => 'admin-a',
            'email' => 'a@example.com',
            'password' => 'password',
            'is_blocked' => false,
        ]);
        $b = User::query()->create([
            'username' => 'admin-b',
            'email' => 'b@example.com',
            'password' => 'password',
            'is_blocked' => false,
        ]);

        UserRole::query()->create([
            'user_id' => $a->id,
            'role_id' => $adminRole->id,
            'assigned_by' => $a->id,
            'assigned_at' => now(),
        ]);
        UserRole::query()->create([
            'user_id' => $b->id,
            'role_id' => $adminRole->id,
            'assigned_by' => $a->id,
            'assigned_at' => now(),
        ]);

        $guard = app(LastAdminGuard::class);
        $guard->assertCanBlockOrDeactivate($b);

        $b->update(['is_blocked' => true]);
        $this->assertSame(1, $guard->activeAdminUserCount());
    }

    protected function createAuthSchema(): void
    {
        foreach (['revoked_roles', 'user_roles', 'roles', 'users', 'activity_log'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('password')->nullable();
            $table->boolean('is_blocked')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestampTz('locked_until')->nullable();
            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->string('code')->unique();
            $table->boolean('is_system')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('assigned_by')->nullable();
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
            $table->unsignedBigInteger('user_role_id');
            $table->unsignedBigInteger('revoked_by')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
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
    }
}
