<?php

namespace Modules\Settings\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Modules\Authentication\Models\User;
use Modules\Authorization\Models\Role;
use Modules\Authorization\Models\UserRole;
use Modules\Settings\Http\Requests\UpdateLibrarySettingsRequest;
use Modules\Settings\Services\SettingsService;
use Modules\Settings\Support\CrossFieldSettingsValidator;
use Modules\Settings\Support\LibrarySettingsAuthorizer;
use Tests\TestCase;

/**
 * TC-SET-02/03/06/07-style coverage for V1-001a (API validation + permissions).
 */
class LibrarySettingsApiTest extends TestCase
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
        foreach (['user_system_actions', 'revoked_roles', 'user_roles', 'roles', 'users', 'setting_values', 'activity_log'] as $table) {
            Schema::dropIfExists($table);
        }
        parent::tearDown();
    }

    public function test_public_endpoint_returns_subset(): void
    {
        $response = $this->getJson('/api/settings/library/public');

        $response->assertOk();
        $keys = collect($response->json('data.settings'))->pluck('key')->unique();
        $this->assertTrue($keys->contains('otp.length'));
        $this->assertFalse($keys->contains('settings.providers'));
        $this->assertFalse($keys->contains('sync.pull_cron'));
    }

    public function test_put_requires_reason_min_length(): void
    {
        $errors = $this->validateUpdate([
            'reason' => 'short',
            'changes' => [
                ['key' => 'fines.grace_days', 'scope' => 'role:student', 'value' => 2],
            ],
        ]);

        $this->assertArrayHasKey('reason', $errors);
    }

    public function test_put_rejects_out_of_range_and_saves_nothing(): void
    {
        $errors = $this->validateUpdate([
            'reason' => 'Raise grace beyond allowed max for test',
            'changes' => [
                ['key' => 'fines.grace_days', 'scope' => 'role:student', 'value' => 999],
            ],
        ]);

        $this->assertNotEmpty($errors);
        $this->assertDatabaseCount('setting_values', 0);
    }

    public function test_put_rejects_cross_field_cap_below_rate(): void
    {
        $settings = app(SettingsService::class);
        $proposed = $settings->effectiveMap();
        $proposed['fines.cap_per_loan|role:student'] = 100;
        $proposed['fines.rate_per_day|role:student'] = 500;

        $violations = app(CrossFieldSettingsValidator::class)->violations($proposed);

        $this->assertNotEmpty($violations);
        $this->assertSame('cap_gte_rate', $violations[0]['rule']);
    }

    public function test_change_batch_is_atomic_on_success(): void
    {
        $settings = app(SettingsService::class);

        $settings->changeBatch(
            [
                ['key' => 'fines.grace_days', 'scope' => 'role:student', 'value' => 2],
                ['key' => 'fines.block_on_overdue', 'scope' => 'global', 'value' => false],
            ],
            'Batch update for TC-SET-02 happy path',
        );

        $this->assertSame(2, $settings->get('fines.grace_days', 'student'));
        $this->assertFalse($settings->get('fines.block_on_overdue'));
        $this->assertDatabaseCount('setting_values', 2);
    }

    public function test_reset_group_writes_registry_defaults(): void
    {
        $settings = app(SettingsService::class);
        $settings->change('fines.grace_days', 5, 'temporary grace raise xx', 'student');

        $settings->resetRequest(
            reason: 'Reset fines group back to defaults xx',
            group: 'fines',
        );

        $this->assertSame(1, $settings->get('fines.grace_days', 'student'));
    }

    public function test_librarian_cannot_update_admin_can(): void
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

        $admin = User::query()->create([
            'username' => 'admin1',
            'email' => 'admin1@example.com',
            'password' => 'password',
            'is_blocked' => false,
        ]);
        $librarian = User::query()->create([
            'username' => 'lib1',
            'email' => 'lib1@example.com',
            'password' => 'password',
            'is_blocked' => false,
        ]);

        UserRole::query()->create([
            'user_id' => $admin->id,
            'role_id' => $adminRole->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
        ]);
        UserRole::query()->create([
            'user_id' => $librarian->id,
            'role_id' => $libRole->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
        ]);

        $auth = app(LibrarySettingsAuthorizer::class);

        $this->assertTrue($auth->canView($librarian));
        $this->assertFalse($auth->canUpdate($librarian));
        $this->assertFalse($auth->canReset($librarian));
        $this->assertTrue($auth->canUpdate($admin));
        $this->assertTrue($auth->canReset($admin));
    }

    public function test_history_lists_append_only_rows(): void
    {
        $settings = app(SettingsService::class);
        $settings->change('fines.grace_days', 2, 'history reason one xx', 'student');
        $settings->change('fines.grace_days', 3, 'history reason two xx', 'student');

        $history = $settings->history('fines.grace_days', 'role:student');

        $this->assertCount(2, $history);
        $this->assertSame(3, $history[0]['value']);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, list<string>>
     */
    protected function validateUpdate(array $payload): array
    {
        $request = UpdateLibrarySettingsRequest::create('/api/settings/library', 'PUT', $payload);
        $request->setContainer(app())->setRedirector(app('redirect'));

        $validator = Validator::make($payload, $request->rules());
        $request->withValidator($validator);
        $validator->fails();

        return $validator->errors()->toArray();
    }

    protected function createSchema(): void
    {
        foreach (['user_system_actions', 'revoked_roles', 'user_roles', 'roles', 'users', 'setting_values', 'activity_log'] as $table) {
            Schema::dropIfExists($table);
        }

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
            $table->string('username')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('password')->nullable();
            $table->boolean('is_blocked')->default(false);
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

        Schema::create('user_system_actions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('system_action_id');
            $table->unsignedBigInteger('user_role_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
