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
use Modules\Authentication\Models\UserProfile;
use Modules\Authentication\Services\UserManagementService;
use Modules\Authorization\Exceptions\AuthorizationException;
use Modules\Authorization\Models\Role;
use Modules\Authorization\Models\SystemAction;
use Modules\Authorization\Models\UserRole;
use Modules\Authorization\Models\UserSystemAction;
use Tests\TestCase;

/**
 * V1-020 / TC-USR-01 / TC-USR-04 — academic fields, photo resize, deactivate.
 */
class UserManagementV2Test extends TestCase
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

    public function test_create_user_with_academic_fields_and_photo(): void
    {
        [$admin, $studentRole] = $this->seedAdminWithUsersManage();
        [$faculty, $department, $programme] = $this->seedAcademicTree();

        Sanctum::actingAs($admin);

        $create = $this->postJson('/api/auth/users', [
            'username' => 'REG2026001',
            'email' => 'member@example.com',
            'phone' => '0712345678',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'fullname' => 'Asha Mwinyi',
            'role_id' => $studentRole->id,
            'faculty_id' => $faculty->id,
            'department_id' => $department->id,
            'programme_id' => $programme->id,
            'year_of_study' => 2,
        ]);

        $create->assertCreated();
        $userId = $create->json('data.id');
        $this->assertSame('+255712345678', $create->json('data.phone'));
        $this->assertSame($faculty->id, $create->json('data.profile.facultyId'));
        $this->assertSame(2, $create->json('data.profile.yearOfStudy'));
        $this->assertSame('manual', $create->json('data.profile.source'));

        $photo = $this->makeJpegUpload(800, 600);
        $upload = $this->post('/api/auth/users/'.$userId.'/photo', [
            'photo' => $photo,
        ], ['Accept' => 'application/json']);

        $upload->assertOk();
        $photoPath = $upload->json('data.profile.photoPath');
        $thumbPath = $upload->json('data.profile.photoThumbPath');

        $this->assertNotEmpty($photoPath);
        $this->assertNotEmpty($thumbPath);
        Storage::disk('local')->assertExists($photoPath);
        Storage::disk('local')->assertExists($thumbPath);

        $mainSize = getimagesize(Storage::disk('local')->path($photoPath));
        $thumbSize = getimagesize(Storage::disk('local')->path($thumbPath));
        $this->assertSame(512, $mainSize[0]);
        $this->assertSame(128, $thumbSize[0]);

        if (function_exists('exif_read_data')) {
            $exif = @exif_read_data(Storage::disk('local')->path($photoPath));
            $this->assertTrue($exif === false || empty($exif['Make'] ?? null));
        }

        $profile = UserProfile::query()->where('user_id', $userId)->first();
        $this->assertNotNull($profile);
        $this->assertSame($photoPath, $profile->photo_path);
    }

    public function test_delete_route_absent_and_deactivate_keeps_user(): void
    {
        [$admin, $studentRole] = $this->seedAdminWithUsersManage();

        Sanctum::actingAs($admin);

        $member = User::query()->create([
            'username' => 'keep-me',
            'email' => 'keep@example.com',
            'password' => 'password',
            'is_blocked' => false,
            'is_active' => true,
        ]);
        UserProfile::query()->create([
            'user_id' => $member->id,
            'fullname' => 'Keep Me',
            'source' => 'manual',
        ]);
        UserRole::query()->create([
            'user_id' => $member->id,
            'role_id' => $studentRole->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
        ]);

        $this->deleteJson('/api/auth/users/'.$member->id)
            ->assertStatus(405);

        $this->assertDatabaseHas('users', [
            'id' => $member->id,
            'username' => 'keep-me',
        ]);

        $this->patchJson('/api/auth/users/'.$member->id.'/active', [
            'active' => false,
            'reason' => 'Graduated',
        ])->assertOk()
            ->assertJsonPath('data.isActive', false)
            ->assertJsonPath('data.deactivatedReason', 'Graduated');

        $this->assertDatabaseHas('users', [
            'id' => $member->id,
            'is_active' => 0,
            'deactivated_reason' => 'Graduated',
        ]);
    }

    public function test_last_admin_cannot_be_deactivated(): void
    {
        [$admin] = $this->seedAdminWithUsersManage();

        Sanctum::actingAs($admin);

        // Self-deactivate is rejected before LAST_ADMIN; service path covers the guard.
        $this->patchJson('/api/auth/users/'.$admin->id.'/active', [
            'active' => false,
            'reason' => 'Should fail',
        ])->assertStatus(422);

        try {
            app(UserManagementService::class)
                ->setActive($admin->id, false, 'Should fail');
            $this->fail('Expected LAST_ADMIN when deactivating the sole admin');
        } catch (AuthorizationException $exception) {
            $this->assertSame('LAST_ADMIN', $exception->errorCode());
        }
    }

    /**
     * @return array{0: User, 1: Role}
     */
    protected function seedAdminWithUsersManage(): array
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
            'name' => 'Manage users',
            'code' => 'users.manage',
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
            'system_action_id' => $manage->id,
            'user_role_id' => $assignment->id,
        ]);

        return [$admin, $studentRole];
    }

    /**
     * @return array{0: Faculty, 1: Department, 2: Programme}
     */
    protected function seedAcademicTree(): array
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
            'name_sw' => 'Sayansi ya Kompyuta',
            'is_active' => true,
        ]);
        $programme = Programme::query()->create([
            'department_id' => $department->id,
            'code' => 'BScCS',
            'name_en' => 'BSc Computer Science',
            'name_sw' => 'Shahada Sayansi ya Kompyuta',
            'is_active' => true,
        ]);

        return [$faculty, $department, $programme];
    }

    protected function makeJpegUpload(int $width, int $height): UploadedFile
    {
        $image = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($image, 40, 120, 200);
        imagefill($image, 0, 0, $bg);
        $tmp = tempnam(sys_get_temp_dir(), 'usrphoto').'.jpg';
        imagejpeg($image, $tmp, 90);
        imagedestroy($image);

        return new UploadedFile($tmp, 'portrait.jpg', 'image/jpeg', null, true);
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
            $table->string('fullname');
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
