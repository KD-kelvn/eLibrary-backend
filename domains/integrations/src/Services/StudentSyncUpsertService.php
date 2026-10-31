<?php

namespace Modules\Integrations\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Academic\Models\Department;
use Modules\Academic\Models\Faculty;
use Modules\Academic\Models\Programme;
use Modules\Authentication\Models\User;
use Modules\Authentication\Repositories\UserProfileRepository;
use Modules\Authentication\Repositories\UserRepository;
use Modules\Authentication\Support\PhoneNumberNormalizer;
use Modules\Authorization\Enums\RoleStatusEnum;
use Modules\Authorization\Models\Role;
use Modules\Authorization\Models\UserRole;

/**
 * Upsert a student from core payload (FR-SYNC-01/03/04).
 *
 * @phpstan-type SyncResult array{action: string, user_id: ?int, error: ?string, meta: array<string, mixed>}
 */
class StudentSyncUpsertService
{
    private const INACTIVE_STATUSES = ['graduated', 'withdrawn', 'suspended'];

    public function __construct(
        private readonly UserRepository $users,
        private readonly UserProfileRepository $profiles,
        private readonly PhoneNumberNormalizer $phones,
    ) {}

    /**
     * @param  array<string, mixed>  $record
     * @return SyncResult
     */
    public function upsert(array $record): array
    {
        $regNo = trim((string) ($record['reg_no'] ?? ''));
        if ($regNo === '') {
            return $this->fail($regNo, 'reg_no is required.');
        }

        [$payload, $errors] = $this->normalizeAndValidate($record);
        if ($errors !== []) {
            return $this->fail($regNo, implode(' ', $errors));
        }

        $role = Role::query()
            ->where('code', 'student')
            ->where('status', RoleStatusEnum::Active->value)
            ->first();

        if (! $role) {
            return $this->fail($regNo, 'student role is not configured.');
        }

        $existing = User::query()
            ->with('profile')
            ->whereRaw('lower(username) = lower(?)', [$regNo])
            ->first();

        $status = strtolower((string) ($payload['status'] ?? 'active'));
        $shouldDeactivate = in_array($status, self::INACTIVE_STATUSES, true);

        if ($existing) {
            return $this->updateExisting($existing, $payload, $role, $shouldDeactivate, $status);
        }

        if ($shouldDeactivate) {
            // Core sent a new inactive student — create inactive so history exists.
        }

        $user = $this->users->create([
            'username' => $regNo,
            'email' => $payload['email'],
            'phone' => $payload['phone'],
            'password' => Str::password(20),
            'is_blocked' => false,
            'is_active' => ! $shouldDeactivate,
            'deactivated_reason' => $shouldDeactivate ? $this->deactivateReason($status) : null,
            'locale' => 'en',
        ]);

        $this->profiles->createForUser($user->id, [
            'first_name' => $payload['first_name'],
            'middle_name' => $payload['middle_name'],
            'last_name' => $payload['last_name'],
            'fullname' => $this->composeName($payload),
            'faculty_id' => $payload['faculty_id'],
            'department_id' => $payload['department_id'],
            'programme_id' => $payload['programme_id'],
            'year_of_study' => $payload['year_of_study'],
            'source' => 'core',
            'core_synced_at' => now(),
        ]);

        $this->ensureStudentRole($user, $role);

        $meta = $this->obligationFlags($user);
        $action = $shouldDeactivate ? 'deactivated' : 'created';

        return [
            'action' => $action,
            'user_id' => $user->id,
            'error' => null,
            'meta' => $meta,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return SyncResult
     */
    private function updateExisting(
        User $user,
        array $payload,
        Role $role,
        bool $shouldDeactivate,
        string $status,
    ): array {
        $email = $payload['email'];
        $phone = $payload['phone'];

        // LP-I3: core wins contact fields unless locally verified.
        if ($user->email_verified_at !== null) {
            $email = $user->email;
        }

        $beforeActive = (bool) $user->is_active;

        $this->users->update($user, [
            'email' => $email,
            'phone' => $phone,
            'is_active' => ! $shouldDeactivate,
            'deactivated_reason' => $shouldDeactivate ? $this->deactivateReason($status) : null,
        ]);

        $this->profiles->updateOrCreateForUser($user->id, [
            'first_name' => $payload['first_name'],
            'middle_name' => $payload['middle_name'],
            'last_name' => $payload['last_name'],
            'fullname' => $this->composeName($payload),
            'faculty_id' => $payload['faculty_id'],
            'department_id' => $payload['department_id'],
            'programme_id' => $payload['programme_id'],
            'year_of_study' => $payload['year_of_study'],
            'source' => 'core',
            'core_synced_at' => now(),
        ]);

        $this->ensureStudentRole($user, $role);

        if ($shouldDeactivate) {
            $user->tokens()->delete();
        }

        $meta = $this->obligationFlags($user);

        if ($shouldDeactivate && $beforeActive) {
            return ['action' => 'deactivated', 'user_id' => $user->id, 'error' => null, 'meta' => $meta];
        }

        if (! $shouldDeactivate && ! $beforeActive) {
            return ['action' => 'updated', 'user_id' => $user->id, 'error' => null, 'meta' => $meta];
        }

        // Detect no-op academic/contact change after apply is expensive; treat as updated when
        // still active and fields may have changed, else unchanged when deactivated already.
        if ($shouldDeactivate && ! $beforeActive) {
            return ['action' => 'unchanged', 'user_id' => $user->id, 'error' => null, 'meta' => $meta];
        }

        return ['action' => 'updated', 'user_id' => $user->id, 'error' => null, 'meta' => $meta];
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array{0: array<string, mixed>, 1: list<string>}
     */
    private function normalizeAndValidate(array $record): array
    {
        $errors = [];
        $payload = [
            'reg_no' => trim((string) ($record['reg_no'] ?? '')),
            'first_name' => trim((string) ($record['first_name'] ?? '')),
            'middle_name' => filled($record['middle_name'] ?? null) ? trim((string) $record['middle_name']) : null,
            'last_name' => trim((string) ($record['last_name'] ?? '')),
            'email' => strtolower(trim((string) ($record['email'] ?? ''))),
            'phone' => $this->phones->normalize(isset($record['phone']) ? (string) $record['phone'] : null),
            'status' => strtolower(trim((string) ($record['status'] ?? 'active'))),
            'year_of_study' => isset($record['year_of_study']) ? (int) $record['year_of_study'] : null,
            'faculty_id' => null,
            'department_id' => null,
            'programme_id' => null,
        ];

        foreach (['first_name', 'last_name', 'email'] as $required) {
            if ($payload[$required] === '' || $payload[$required] === null) {
                $errors[] = "{$required} is required.";
            }
        }

        if ($payload['email'] !== '' && ! filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'email is invalid.';
        }

        if ($payload['phone'] !== null && ! $this->phones->isE164($payload['phone'])) {
            $errors[] = 'phone must be a valid E.164 number.';
        }

        $allowedStatus = ['active', 'graduated', 'withdrawn', 'suspended'];
        if (! in_array($payload['status'], $allowedStatus, true)) {
            $errors[] = 'status is invalid.';
        }

        if ($payload['year_of_study'] !== null && ($payload['year_of_study'] < 1 || $payload['year_of_study'] > 10)) {
            $errors[] = 'year_of_study must be between 1 and 10.';
        }

        $facultyCode = filled($record['faculty_code'] ?? null) ? (string) $record['faculty_code'] : null;
        $departmentCode = filled($record['department_code'] ?? null) ? (string) $record['department_code'] : null;
        $programmeCode = filled($record['programme_code'] ?? null) ? (string) $record['programme_code'] : null;

        if ($facultyCode) {
            $faculty = Faculty::query()
                ->whereRaw('lower(code) = lower(?)', [$facultyCode])
                ->where('is_active', true)
                ->first();
            if (! $faculty) {
                $errors[] = 'unknown faculty_code.';
            } else {
                $payload['faculty_id'] = $faculty->id;
            }
        }

        if ($departmentCode) {
            $department = Department::query()
                ->whereRaw('lower(code) = lower(?)', [$departmentCode])
                ->where('is_active', true)
                ->first();
            if (! $department) {
                $errors[] = 'unknown department_code.';
            } else {
                $payload['department_id'] = $department->id;
                $payload['faculty_id'] = $payload['faculty_id'] ?? $department->faculty_id;
            }
        }

        if ($programmeCode) {
            $programme = Programme::query()
                ->with('department')
                ->whereRaw('lower(code) = lower(?)', [$programmeCode])
                ->where('is_active', true)
                ->first();
            if (! $programme) {
                $errors[] = 'unknown programme_code.';
            } else {
                $payload['programme_id'] = $programme->id;
                $payload['department_id'] = $payload['department_id'] ?? $programme->department_id;
                $payload['faculty_id'] = $payload['faculty_id'] ?? $programme->department?->faculty_id;
            }
        }

        if (filled($payload['email'])) {
            $other = User::query()
                ->whereRaw('lower(email) = ?', [$payload['email']])
                ->whereRaw('lower(username) <> lower(?)', [$payload['reg_no']])
                ->exists();
            if ($other) {
                $errors[] = 'email already belongs to another user.';
            }
        }

        if (filled($payload['phone'])) {
            $otherPhone = User::query()
                ->where('phone', $payload['phone'])
                ->whereRaw('lower(username) <> lower(?)', [$payload['reg_no']])
                ->exists();
            if ($otherPhone) {
                $errors[] = 'phone already belongs to another user.';
            }
        }

        return [$payload, $errors];
    }

    private function ensureStudentRole(User $user, Role $role): void
    {
        $has = UserRole::query()
            ->where('user_id', $user->id)
            ->where('role_id', $role->id)
            ->active()
            ->exists();

        if ($has) {
            return;
        }

        UserRole::query()->create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            // Core push has no human actor; attribute to the member record.
            'assigned_by' => $user->id,
            'assigned_at' => now(),
        ]);
    }

    /**
     * Flag open circulation obligations for librarians (FR-SYNC-03).
     *
     * @return array<string, mixed>
     */
    private function obligationFlags(User $user): array
    {
        $flags = [];

        try {
            $schema = Schema::getConnection()->getDriverName() === 'pgsql'
                ? 'book_borrowing.borrowing_requests'
                : 'borrowing_requests';

            if (! Schema::hasTable($schema)
                && ! Schema::hasTable('borrowing_requests')) {
                return $flags;
            }

            $table = Schema::hasTable($schema)
                ? $schema
                : 'borrowing_requests';

            $open = DB::table($table)
                ->where('user_id', $user->id)
                ->whereNull('deleted_at')
                ->whereIn('status', ['pending', 'approved', 'on_loan', 'overdue', 'issued'])
                ->count();

            if ($open > 0) {
                $flags['open_loans'] = $open;
            }
        } catch (\Throwable) {
            // Loans domain may not be migrated yet.
        }

        return $flags;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function composeName(array $payload): string
    {
        return trim(implode(' ', array_filter([
            $payload['first_name'] ?? null,
            $payload['middle_name'] ?? null,
            $payload['last_name'] ?? null,
        ])));
    }

    private function deactivateReason(string $status): string
    {
        return 'University sync: '.$status;
    }

    /**
     * @return SyncResult
     */
    private function fail(string $regNo, string $error): array
    {
        return [
            'action' => 'failed',
            'user_id' => null,
            'error' => $error,
            'meta' => ['reg_no' => $regNo],
        ];
    }
}
