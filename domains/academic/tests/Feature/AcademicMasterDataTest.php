<?php

namespace Modules\Academic\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Modules\Authentication\Models\User;
use Modules\Authorization\Models\Role;
use Modules\Authorization\Models\SystemAction;
use Modules\Authorization\Models\UserRole;
use Modules\Authorization\Models\UserSystemAction;
use Modules\Authorization\Services\PermissionChecker;
use Tests\TestCase;

class AcademicMasterDataTest extends TestCase
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
            'courses',
            'programmes',
            'departments',
            'faculties',
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

    public function test_create_faculty_list_update_and_department_hierarchy(): void
    {
        [$manager] = $this->seedUsersWithAcademicManage();

        Sanctum::actingAs($manager);

        $create = $this->postJson('/api/academic/faculties', [
            'code' => 'ENG',
            'name_en' => 'Engineering',
            'name_sw' => 'Uhandisi',
        ]);
        $create->assertCreated();
        $facultyId = $create->json('data.id');
        $this->assertSame('ENG', $create->json('data.code'));

        $this->postJson('/api/academic/faculties', [
            'code' => 'ENG',
            'name_en' => 'Duplicate',
        ])->assertUnprocessable();

        $this->getJson('/api/academic/faculties')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1);

        $this->putJson("/api/academic/faculties/{$facultyId}", [
            'name_en' => 'Engineering Faculty',
        ])->assertOk()
            ->assertJsonPath('data.nameEn', 'Engineering Faculty');

        $department = $this->postJson('/api/academic/departments', [
            'faculty_id' => $facultyId,
            'code' => 'CIV',
            'name_en' => 'Civil Engineering',
        ]);
        $department->assertCreated();
        $this->assertSame($facultyId, $department->json('data.facultyId'));

        $this->deleteJson("/api/academic/faculties/{$facultyId}")
            ->assertUnprocessable();
    }

    public function test_post_requires_academic_manage_grant(): void
    {
        [, $viewer] = $this->seedUsersWithAcademicManage();

        Sanctum::actingAs($viewer);

        $this->postJson('/api/academic/faculties', [
            'code' => 'SCI',
            'name_en' => 'Science',
        ])->assertForbidden();
    }

    public function test_permission_checker_reflects_academic_manage_grant(): void
    {
        [$manager, $viewer] = $this->seedUsersWithAcademicManage();
        $checker = app(PermissionChecker::class);

        $this->assertTrue($checker->allowsOne($manager, 'academic.manage'));
        $this->assertFalse($checker->allowsOne($viewer, 'academic.manage'));
    }

    /**
     * @return array{0: User, 1: User}
     */
    protected function seedUsersWithAcademicManage(): array
    {
        $adminRole = Role::query()->create([
            'name' => 'System Admin',
            'code' => 'admin',
            'status' => 'active',
            'is_system' => true,
        ]);
        $studentRole = Role::query()->create([
            'name' => 'Student',
            'code' => 'student',
            'status' => 'active',
            'is_system' => true,
        ]);

        $manage = SystemAction::query()->create([
            'name' => 'Manage academic master data',
            'code' => 'academic.manage',
            'role_id' => $adminRole->id,
            'action_type' => 'permission',
            'is_active' => true,
        ]);

        $manager = User::query()->create([
            'username' => 'academic-manager',
            'email' => 'academic-manager@example.com',
            'password' => 'password',
            'is_blocked' => false,
        ]);
        $viewer = User::query()->create([
            'username' => 'academic-viewer',
            'email' => 'academic-viewer@example.com',
            'password' => 'password',
            'is_blocked' => false,
        ]);

        $managerAssignment = UserRole::query()->create([
            'user_id' => $manager->id,
            'role_id' => $adminRole->id,
            'assigned_by' => $manager->id,
            'assigned_at' => now(),
        ]);
        $viewerAssignment = UserRole::query()->create([
            'user_id' => $viewer->id,
            'role_id' => $studentRole->id,
            'assigned_by' => $manager->id,
            'assigned_at' => now(),
        ]);

        UserSystemAction::query()->create([
            'user_id' => $manager->id,
            'system_action_id' => $manage->id,
            'user_role_id' => $managerAssignment->id,
        ]);

        return [$manager, $viewer];
    }

    protected function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_blocked')->default(false);
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

        Schema::create('faculties', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name_en');
            $table->string('name_sw')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id');
            $table->string('code')->unique();
            $table->string('name_en');
            $table->string('name_sw')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('programmes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id');
            $table->string('code')->unique();
            $table->string('name_en');
            $table->string('name_sw')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programme_id');
            $table->string('code')->unique();
            $table->string('name_en');
            $table->string('name_sw')->nullable();
            $table->boolean('is_active')->default(true);
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
    }
}
