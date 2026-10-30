<?php

namespace Modules\Authentication\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Modules\Academic\Models\Department;
use Modules\Academic\Models\Faculty;
use Modules\Academic\Models\Programme;
use Modules\Authentication\Models\User;
use Modules\Authentication\Models\UserImport;
use Modules\Authentication\Models\UserImportRow;
use Modules\Authorization\Models\Role;
use Modules\Authorization\Models\SystemAction;
use Modules\Authorization\Models\UserRole;
use Modules\Authorization\Models\UserSystemAction;
use Tests\TestCase;

/**
 * V1-022 / TC-USR-02 / TC-USR-03 — bulk import preview, commit, idempotent re-run.
 */
class UserImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['audit.enabled' => false]);
        Storage::fake('local');
        $this->createSchema();
    }

    protected function tearDown(): void
    {
        foreach ([
            'user_import_rows',
            'user_imports',
            'user_profiles',
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
            'setting_values',
            'personal_access_tokens',
            'activity_log',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        parent::tearDown();
    }

    public function test_preview_flags_invalid_rows_and_commit_imports_only_valid(): void
    {
        [$admin, $studentRole] = $this->seedAdminWithImportPermission();
        $this->seedAcademicTree();
        Sanctum::actingAs($admin);

        $csv = implode("\n", [
            'username,email,phone,first_name,middle_name,last_name,faculty_code,department_code,programme_code,year_of_study',
            'REG001,ok@example.com,0712345678,Asha,,Mwinyi,ENG,CSE,BSC-CS,2',
            'REG002,bad-phone@example.com,123,Bad,,Phone,ENG,CSE,BSC-CS,1',
            'REG003,ok2@example.com,0711111111,Good,,Student,ZZZ,CSE,BSC-CS,1',
        ]);

        $upload = UploadedFile::fake()->createWithContent('members.csv', $csv);

        $preview = $this->post('/api/auth/user-imports', [
            'type' => 'student',
            'file' => $upload,
        ], ['Accept' => 'application/json']);

        $preview->assertOk();
        $importId = $preview->json('data.import_id');
        $this->assertSame(1, $preview->json('data.summary.valid'));
        $this->assertSame(2, $preview->json('data.summary.invalid'));
        $this->assertNotEmpty($preview->json('data.errors'));

        $commit = $this->postJson('/api/auth/user-imports/'.$importId.'/commit');
        $commit->assertOk();
        $commit->assertJsonPath('data.created', 1);
        $commit->assertJsonPath('data.failed', 2);

        $this->assertDatabaseHas('users', ['username' => 'REG001', 'email' => 'ok@example.com']);
        $this->assertDatabaseMissing('users', ['username' => 'REG002']);
        $this->assertDatabaseMissing('users', ['username' => 'REG003']);
        $this->assertNotNull($studentRole);
    }

    public function test_reimport_same_file_reports_unchanged(): void
    {
        [$admin] = $this->seedAdminWithImportPermission();
        $this->seedAcademicTree();
        Sanctum::actingAs($admin);

        $csv = implode("\n", [
            'username,email,phone,first_name,middle_name,last_name,faculty_code,department_code,programme_code,year_of_study',
            'REG100,member@example.com,0712345678,Asha,,Mwinyi,ENG,CSE,BSC-CS,2',
        ]);

        $first = $this->post('/api/auth/user-imports', [
            'type' => 'student',
            'file' => UploadedFile::fake()->createWithContent('a.csv', $csv),
        ], ['Accept' => 'application/json']);
        $first->assertOk();
        $this->postJson('/api/auth/user-imports/'.$first->json('data.import_id').'/commit')
            ->assertOk()
            ->assertJsonPath('data.created', 1);

        $second = $this->post('/api/auth/user-imports', [
            'type' => 'student',
            'file' => UploadedFile::fake()->createWithContent('a-again.csv', $csv),
        ], ['Accept' => 'application/json']);
        $second->assertOk();

        $this->postJson('/api/auth/user-imports/'.$second->json('data.import_id').'/commit')
            ->assertOk()
            ->assertJsonPath('data.created', 0)
            ->assertJsonPath('data.unchanged', 1)
            ->assertJsonPath('data.updated', 0);

        $this->assertSame(1, User::query()->where('username', 'REG100')->count());
        $this->assertSame(2, UserImport::query()->count());
        $this->assertGreaterThan(0, UserImportRow::query()->count());
    }

    public function test_template_download_returns_file(): void
    {
        [$admin] = $this->seedAdminWithImportPermission();
        Sanctum::actingAs($admin);

        $response = $this->get('/api/auth/user-imports/template?type=student&format=csv');
        $response->assertOk();
        $this->assertStringContainsString('username', $response->streamedContent());
    }

    /**
     * @return array{0: User, 1: Role}
     */
    protected function seedAdminWithImportPermission(): array
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

        $import = SystemAction::query()->create([
            'name' => 'Import users',
            'code' => 'users.import',
            'role_id' => $adminRole->id,
            'action_type' => 'permission',
            'is_active' => true,
        ]);

        $admin = User::query()->create([
            'username' => 'sys-admin',
            'email' => 'sys-admin@example.com',
            'password' => 'password',
            'is_blocked' => false,
            'is_active' => true,
        ]);

        $assignment = UserRole::query()->create([
            'user_id' => $admin->id,
            'role_id' => $adminRole->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
        ]);

        UserSystemAction::query()->create([
            'user_id' => $admin->id,
            'system_action_id' => $import->id,
            'user_role_id' => $assignment->id,
        ]);

        return [$admin, $studentRole];
    }

    protected function seedAcademicTree(): void
    {
        $faculty = Faculty::query()->create([
            'code' => 'ENG',
            'name_en' => 'Engineering',
            'name_sw' => 'Uhandisi',
            'is_active' => true,
        ]);
        $department = Department::query()->create([
            'faculty_id' => $faculty->id,
            'code' => 'CSE',
            'name_en' => 'Computer Science',
            'name_sw' => 'Sayansi',
            'is_active' => true,
        ]);
        Programme::query()->create([
            'department_id' => $department->id,
            'code' => 'BSC-CS',
            'name_en' => 'BSc Computer Science',
            'name_sw' => 'BSc',
            'is_active' => true,
        ]);
    }

    protected function createSchema(): void
    {
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
            $table->timestampTz('locked_until')->nullable();
            $table->string('locale', 10)->default('en');
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
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('gender')->nullable();
            $table->date('dob')->nullable();
            $table->string('profile_picture')->nullable();
            $table->unsignedBigInteger('faculty_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('programme_id')->nullable();
            $table->unsignedTinyInteger('year_of_study')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('photo_thumb_path')->nullable();
            $table->string('source', 20)->default('manual');
            $table->timestampTz('core_synced_at')->nullable();
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

        Schema::create('user_imports', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->string('status', 20)->default('preview');
            $table->string('original_filename');
            $table->string('mime')->nullable();
            $table->string('storage_path')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamp('committed_at')->nullable();
            $table->json('summary')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('user_import_rows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_import_id');
            $table->unsignedInteger('row_number');
            $table->json('payload');
            $table->string('status', 20)->default('valid');
            $table->json('errors')->nullable();
            $table->unsignedBigInteger('matched_user_id')->nullable();
            $table->timestamps();
            $table->unique(['user_import_id', 'row_number']);
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
