<?php

namespace Modules\Integrations\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Modules\Academic\Models\Department;
use Modules\Academic\Models\Faculty;
use Modules\Academic\Models\Programme;
use Modules\Authentication\Models\User;
use Modules\Authorization\Models\Role;
use Modules\Authorization\Models\SystemAction;
use Modules\Authorization\Models\UserRole;
use Modules\Authorization\Models\UserSystemAction;
use Modules\Integrations\Models\SyncRun;
use Tests\TestCase;

/**
 * V1-024 / TC-SYNC-02 — pull with cursor across pages + run now.
 */
class UniversitySyncPullTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'audit.enabled' => false,
            'services.university.base_url' => 'https://core.test',
            'services.university.students_path' => '/students',
            'services.university.api_key' => 'core-key',
            'services.university.auth_scheme' => 'bearer',
        ]);
        $this->createSchema();
        $this->seedAcademicTree();
        Role::query()->create([
            'name' => 'Student',
            'code' => 'student',
            'status' => 'active',
            'is_system' => true,
        ]);
    }

    protected function tearDown(): void
    {
        foreach ([
            'sync_run_items',
            'sync_runs',
            'notifications',
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
            'provider_settings',
            'personal_access_tokens',
            'activity_log',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        parent::tearDown();
    }

    public function test_pull_pages_students_and_advances_cursor(): void
    {
        Http::fake([
            'core.test/students*' => Http::sequence()
                ->push([
                    'data' => [
                        $this->student('REG-P1', 'p1@example.com', '2026-10-01T10:00:00+03:00'),
                    ],
                    'next_page' => 2,
                ])
                ->push([
                    'data' => [
                        $this->student('REG-P2', 'p2@example.com', '2026-10-02T11:00:00+03:00'),
                    ],
                    'next_page' => null,
                ]),
        ]);

        $this->artisan('university:sync-pull', ['--force' => true])
            ->assertSuccessful();

        $run = SyncRun::query()->where('mode', 'pull')->first();
        $this->assertNotNull($run);
        $this->assertSame('completed', $run->status);
        $this->assertSame(2, $run->created_count);
        $this->assertSame('2026-10-02T11:00:00+03:00', $run->cursor);

        $this->assertDatabaseHas('users', ['username' => 'REG-P1']);
        $this->assertDatabaseHas('users', ['username' => 'REG-P2']);

        Http::assertSentCount(2);
    }

    public function test_second_pull_sends_previous_cursor_as_updated_since(): void
    {
        SyncRun::query()->create([
            'mode' => 'pull',
            'status' => 'completed',
            'cursor' => '2026-10-05T08:00:00+03:00',
            'finished_at' => now(),
        ]);

        Http::fake([
            'core.test/students*' => Http::response([
                'data' => [
                    $this->student('REG-P3', 'p3@example.com', '2026-10-06T09:00:00+03:00'),
                ],
                'next_page' => null,
            ]),
        ]);

        $this->artisan('university:sync-pull', ['--force' => true])->assertSuccessful();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://core.test/students?page=1&updated_since='.urlencode('2026-10-05T08:00:00+03:00')
                || str_contains($request->url(), 'updated_since=2026-10-05T08%3A00%3A00%2B03%3A00')
                || str_contains($request->url(), 'updated_since=2026-10-05');
        });
    }

    public function test_admin_run_now_queues_pull(): void
    {
        [$admin] = $this->seedAdminWithSyncRunPermission();
        Sanctum::actingAs($admin);

        Http::fake([
            'core.test/students*' => Http::response(['data' => [], 'next_page' => null]),
        ]);

        $this->postJson('/api/integrations/sync-runs')
            ->assertStatus(202)
            ->assertJsonPath('data.status', fn ($s) => in_array($s, ['queued', 'completed'], true));

        $this->assertDatabaseHas('sync_runs', [
            'mode' => 'pull',
            'triggered_by' => $admin->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function student(string $reg, string $email, string $updatedAt): array
    {
        return [
            'reg_no' => $reg,
            'first_name' => 'Asha',
            'middle_name' => null,
            'last_name' => 'Mushi',
            'faculty_code' => 'ENG',
            'department_code' => 'CSE',
            'programme_code' => 'BSC-CS',
            'year_of_study' => 2,
            'email' => $email,
            'phone' => '+2557'.substr(preg_replace('/\D/', '', md5($reg).'99999999') ?? '99999999', 0, 8),
            'status' => 'active',
            'updated_at' => $updatedAt,
        ];
    }

    /** @return array{0: User} */
    private function seedAdminWithSyncRunPermission(): array
    {
        $adminRole = Role::query()->create([
            'name' => 'System Admin',
            'code' => 'admin',
            'status' => 'active',
            'is_system' => true,
        ]);

        $run = SystemAction::query()->create([
            'name' => 'Run sync',
            'code' => 'sync.run',
            'role_id' => $adminRole->id,
            'action_type' => 'permission',
            'is_active' => true,
        ]);
        $view = SystemAction::query()->create([
            'name' => 'View sync',
            'code' => 'sync.view',
            'role_id' => $adminRole->id,
            'action_type' => 'permission',
            'is_active' => true,
        ]);

        $admin = User::query()->create([
            'username' => 'pull-admin',
            'email' => 'pull-admin@example.com',
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

        foreach ([$run, $view] as $action) {
            UserSystemAction::query()->create([
                'user_id' => $admin->id,
                'system_action_id' => $action->id,
                'user_role_id' => $assignment->id,
            ]);
        }

        return [$admin];
    }

    private function seedAcademicTree(): void
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

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('phone')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
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

        Schema::create('sync_runs', function (Blueprint $table) {
            $table->id();
            $table->string('mode', 20);
            $table->string('status', 20)->default('queued');
            $table->string('idempotency_key')->nullable()->unique();
            $table->string('cursor')->nullable();
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('deactivated_count')->default(0);
            $table->unsignedInteger('unchanged_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedBigInteger('triggered_by')->nullable();
            $table->text('error_summary')->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sync_run_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sync_run_id');
            $table->string('reg_no');
            $table->string('action', 20);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('error')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('setting_values', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('scope')->default('global');
            $table->text('value')->nullable();
            $table->timestampTz('effective_from')->nullable();
            $table->timestampTz('effective_to')->nullable();
            $table->string('reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('provider_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group')->unique();
            $table->text('config')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
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
