<?php

namespace Modules\Authentication\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\NewAccessToken;
use Modules\Authentication\Models\PersonalAccessToken;
use Modules\Authentication\Models\User;
use Modules\Authentication\Services\SessionIdleService;
use Modules\Authentication\Services\SessionService;
use Modules\Authorization\Models\Role;
use Modules\Authorization\Models\UserRole;
use Modules\Authorization\Services\AccessManagement\RoleAssignmentService;
use Tests\TestCase;

/**
 * TC-AUTH-09 — idle timeout by role + revoke; token rotate on role change (V1-013 / FR-AUTH-09/10).
 */
class SessionIdleTimeoutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['audit.enabled' => false]);
        Cache::flush();
        $this->createSchema();
    }

    protected function tearDown(): void
    {
        foreach ([
            'personal_access_tokens',
            'user_system_actions',
            'system_action_roles',
            'system_actions',
            'revoked_roles',
            'user_roles',
            'roles',
            'user_profiles',
            'setting_values',
            'activity_log',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        parent::tearDown();
    }

    public function test_idle_staff_token_is_rejected_with_session_idle_code(): void
    {
        $admin = $this->seedUserWithRole('admin', 'admin@example.com');
        $token = $this->issueToken($admin);
        $this->ageToken($token, minutes: 31);

        $response = $this->withToken($token->plainTextToken)->postJson('/api/auth/logout');

        $response->assertUnauthorized();
        $response->assertJsonPath('code', 'SESSION_IDLE');
        $this->assertSame(0, PersonalAccessToken::query()->count());
    }

    public function test_staff_token_within_idle_window_still_works(): void
    {
        $admin = $this->seedUserWithRole('admin', 'admin@example.com');
        $this->assertSame(30, app(SessionIdleService::class)->idleTimeoutMinutesFor($admin));

        $token = $this->issueToken($admin);
        $this->ageToken($token, minutes: 10);

        $this
            ->withToken($token->plainTextToken)
            ->postJson('/api/auth/logout')
            ->assertOk();
    }

    public function test_member_idle_uses_seven_day_default(): void
    {
        $member = $this->seedUserWithRole('student', 'member@example.com');
        $this->assertSame(10_080, app(SessionIdleService::class)->idleTimeoutMinutesFor($member));

        $token = $this->issueToken($member);
        $this->ageToken($token, minutes: 60);

        $this
            ->withToken($token->plainTextToken)
            ->postJson('/api/auth/logout')
            ->assertOk();
    }

    public function test_most_restrictive_role_wins_idle_timeout(): void
    {
        $user = $this->seedUserWithRole('student', 'multi@example.com');
        $adminRole = Role::query()->create([
            'name' => 'Admin',
            'code' => 'admin',
            'status' => 'active',
            'is_system' => true,
        ]);
        UserRole::query()->create([
            'user_id' => $user->id,
            'role_id' => $adminRole->id,
            'assigned_at' => now(),
        ]);

        $minutes = app(SessionIdleService::class)->idleTimeoutMinutesFor($user->fresh());

        $this->assertSame(30, $minutes);
    }

    public function test_role_assignment_rotates_existing_tokens(): void
    {
        $user = $this->seedUserWithRole('student', 'rotate@example.com');
        $this->issueToken($user);
        $this->issueToken($user);
        $this->assertSame(2, PersonalAccessToken::query()->where('tokenable_id', $user->id)->count());

        $librarian = Role::query()->create([
            'name' => 'Librarian',
            'code' => 'librarian',
            'status' => 'active',
            'is_system' => true,
        ]);

        app(RoleAssignmentService::class)->store([
            'user_id' => $user->id,
            'role_id' => $librarian->id,
        ], assignedBy: $user->id);

        $this->assertSame(0, PersonalAccessToken::query()->where('tokenable_id', $user->id)->count());
    }

    public function test_revoking_a_session_rejects_that_token(): void
    {
        $user = $this->seedUserWithRole('student', 'revoke@example.com');
        $keep = $this->issueToken($user);
        $drop = $this->issueToken($user);

        app(SessionService::class)
            ->revoke($drop->accessToken->fresh());

        $this->assertNull(PersonalAccessToken::query()->find($drop->accessToken->id));

        $this
            ->withToken($drop->plainTextToken)
            ->postJson('/api/auth/logout')
            ->assertUnauthorized();

        $this
            ->withToken($keep->plainTextToken)
            ->postJson('/api/auth/logout')
            ->assertOk();
    }

    protected function seedUserWithRole(string $roleCode, string $email): User
    {
        $user = User::query()->create([
            'username' => str_replace('@', '.', $email),
            'email' => $email,
            'phone' => null,
            'password' => 'password',
            'is_blocked' => false,
            'is_active' => true,
            'locale' => 'en',
        ]);

        $role = Role::query()->firstOrCreate(
            ['code' => $roleCode],
            [
                'name' => ucfirst($roleCode),
                'status' => 'active',
                'is_system' => true,
            ],
        );

        UserRole::query()->create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'assigned_at' => now(),
        ]);

        return $user;
    }

    protected function issueToken(User $user): NewAccessToken
    {
        return $user->createToken('phpunit');
    }

    protected function ageToken(NewAccessToken $token, int $minutes): void
    {
        $token->accessToken->forceFill([
            'last_used_at' => now()->subMinutes($minutes),
        ])->save();
    }

    protected function createSchema(): void
    {
        Schema::create('setting_values', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('scope');
            $table->json('value');
            $table->timestamp('effective_from');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
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
            $table->timestamp('locked_until')->nullable();
            $table->string('locale', 10)->default('en');
            $table->rememberToken();
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
            $table->timestamps();
        });

        Schema::create('user_system_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('system_action_id');
            $table->foreignId('user_role_id')->nullable();
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
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }
}
