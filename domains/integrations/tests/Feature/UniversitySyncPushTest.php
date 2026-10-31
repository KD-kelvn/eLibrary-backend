<?php

namespace Modules\Integrations\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Notification;
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
use Modules\Integrations\Notifications\SyncRunFailedNotification;
use Tests\TestCase;

/**
 * V1-023 / TC-SYNC-01/03/04/05 — HMAC push, deactivate, contact conflict, sync log.
 */
class UniversitySyncPushTest extends TestCase
{
    private const SECRET = 'test-university-hmac-secret';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'audit.enabled' => false,
            'services.university.hmac_secret' => self::SECRET,
            'services.university.timestamp_tolerance_seconds' => 300,
        ]);
        Notification::fake();
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

    public function test_push_valid_batch_creates_students_and_run_log(): void
    {
        $payload = [
            'students' => [
                $this->student('TIPM/BIT/2024/0012', 'asha@student.tipm.ac.tz', 'active'),
            ],
        ];

        $response = $this->signedPost($payload, 'idem-1');

        $response->assertStatus(202)
            ->assertJsonPath('data.run_id', fn ($id) => is_int($id) || ctype_digit((string) $id));

        $runId = (int) $response->json('data.run_id');
        $this->assertDatabaseHas('sync_runs', [
            'id' => $runId,
            'mode' => 'push',
            'status' => 'completed',
            'created_count' => 1,
            'idempotency_key' => 'idem-1',
        ]);
        $this->assertDatabaseHas('users', [
            'username' => 'TIPM/BIT/2024/0012',
            'email' => 'asha@student.tipm.ac.tz',
            'is_active' => 1,
        ]);
        $user = User::query()->where('username', 'TIPM/BIT/2024/0012')->first();
        $this->assertSame('core', $user->profile->source);
        $this->assertNotNull($user->profile->core_synced_at);
    }

    public function test_bad_signature_and_stale_timestamp_are_rejected(): void
    {
        $body = json_encode(['students' => [$this->student('R1', 'a@example.com', 'active')]], JSON_THROW_ON_ERROR);
        $ts = (string) now()->getTimestamp();

        $this->call(
            'POST',
            '/api/integrations/university/students',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X-Signature' => 'sha256=deadbeef',
                'HTTP_X-Timestamp' => $ts,
                'HTTP_Idempotency-Key' => 'bad-sig',
            ],
            $body,
        )->assertStatus(401);

        $stale = (string) (now()->getTimestamp() - 600);
        $sig = $this->sign($stale, $body);
        $this->call(
            'POST',
            '/api/integrations/university/students',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X-Signature' => 'sha256='.$sig,
                'HTTP_X-Timestamp' => $stale,
                'HTTP_Idempotency-Key' => 'stale',
            ],
            $body,
        )->assertStatus(401);
    }

    public function test_idempotency_replay_returns_same_run_without_duplicate_users(): void
    {
        $payload = [
            'students' => [
                $this->student('REG-IDEMP', 'idem@example.com', 'active'),
            ],
        ];

        $first = $this->signedPost($payload, 'same-key')->assertStatus(202);
        $second = $this->signedPost($payload, 'same-key')->assertStatus(202);

        $this->assertSame($first->json('data.run_id'), $second->json('data.run_id'));
        $this->assertTrue($second->json('data.replayed'));
        $this->assertSame(1, User::query()->where('username', 'REG-IDEMP')->count());
        $this->assertSame(1, SyncRun::query()->count());
    }

    public function test_graduated_student_is_deactivated_and_tokens_revoked(): void
    {
        $create = $this->signedPost([
            'students' => [$this->student('REG-GRAD', 'grad@example.com', 'active')],
        ], 'grad-1')->assertStatus(202);

        $user = User::query()->where('username', 'REG-GRAD')->firstOrFail();
        $user->createToken('device');
        $this->assertSame(1, $user->tokens()->count());

        $this->signedPost([
            'students' => [$this->student('REG-GRAD', 'grad@example.com', 'graduated')],
        ], 'grad-2')->assertStatus(202);

        $user->refresh();
        $this->assertFalse((bool) $user->is_active);
        $this->assertSame(0, $user->tokens()->count());
        $this->assertDatabaseHas('sync_run_items', [
            'reg_no' => 'REG-GRAD',
            'action' => 'deactivated',
        ]);
        $this->assertNotNull($create->json('data.run_id'));
    }

    public function test_locally_verified_email_is_kept_when_core_sends_different_email(): void
    {
        $this->signedPost([
            'students' => [$this->student('REG-MAIL', 'local@example.com', 'active')],
        ], 'mail-1')->assertStatus(202);

        $user = User::query()->where('username', 'REG-MAIL')->firstOrFail();
        $user->forceFill(['email_verified_at' => now()])->save();

        $this->signedPost([
            'students' => [$this->student('REG-MAIL', 'core@example.com', 'active')],
        ], 'mail-2')->assertStatus(202);

        $user->refresh();
        $this->assertSame('local@example.com', $user->email);
    }

    public function test_failed_items_alert_admins_and_appear_in_sync_log(): void
    {
        [$admin] = $this->seedAdminWithSyncPermission();

        $this->signedPost([
            'students' => [
                $this->student('REG-BAD', 'bad@example.com', 'active', facultyCode: 'NOPE'),
            ],
        ], 'fail-1')->assertStatus(202);

        $this->assertDatabaseHas('sync_runs', [
            'idempotency_key' => 'fail-1',
            'failed_count' => 1,
            'status' => 'failed',
        ]);
        $this->assertDatabaseHas('sync_run_items', [
            'reg_no' => 'REG-BAD',
            'action' => 'failed',
        ]);

        Notification::assertSentTo($admin, SyncRunFailedNotification::class);

        Sanctum::actingAs($admin);
        $list = $this->getJson('/api/integrations/sync-runs');
        $list->assertOk();
        $this->assertGreaterThanOrEqual(1, count($list->json('data.items')));

        $runId = SyncRun::query()->where('idempotency_key', 'fail-1')->value('id');
        $this->getJson('/api/integrations/sync-runs/'.$runId)
            ->assertOk()
            ->assertJsonPath('data.counts.failed', 1);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function signedPost(array $payload, string $idempotencyKey)
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $ts = (string) now()->getTimestamp();
        $sig = $this->sign($ts, $body);

        return $this->call(
            'POST',
            '/api/integrations/university/students',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X-Signature' => 'sha256='.$sig,
                'HTTP_X-Timestamp' => $ts,
                'HTTP_Idempotency-Key' => $idempotencyKey,
            ],
            $body,
        );
    }

    private function sign(string $timestamp, string $body): string
    {
        return hash_hmac('sha256', $timestamp.'.'.$body, self::SECRET);
    }

    /**
     * @return array<string, mixed>
     */
    private function student(
        string $regNo,
        string $email,
        string $status,
        string $facultyCode = 'ENG',
    ): array {
        return [
            'reg_no' => $regNo,
            'first_name' => 'Asha',
            'middle_name' => null,
            'last_name' => 'Mushi',
            'faculty_code' => $facultyCode,
            'department_code' => 'CSE',
            'programme_code' => 'BSC-CS',
            'year_of_study' => 2,
            'email' => $email,
            'phone' => '+255712345678',
            'status' => $status,
            'updated_at' => now()->toIso8601String(),
        ];
    }

    /** @return array{0: User} */
    private function seedAdminWithSyncPermission(): array
    {
        $adminRole = Role::query()->create([
            'name' => 'System Admin',
            'code' => 'admin',
            'status' => 'active',
            'is_system' => true,
        ]);

        $view = SystemAction::query()->create([
            'name' => 'View sync',
            'code' => 'sync.view',
            'role_id' => $adminRole->id,
            'action_type' => 'permission',
            'is_active' => true,
        ]);

        $admin = User::query()->create([
            'username' => 'sync-admin',
            'email' => 'sync-admin@example.com',
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
            'system_action_id' => $view->id,
            'user_role_id' => $assignment->id,
        ]);

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
