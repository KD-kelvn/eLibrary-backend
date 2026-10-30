<?php

namespace Modules\Authentication\Services;

use App\Support\AdminActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Models\Department;
use Modules\Academic\Models\Programme;
use Modules\Authentication\Exceptions\AuthenticationException;
use Modules\Authentication\Models\User;
use Modules\Authentication\Repositories\UserProfileRepository;
use Modules\Authentication\Repositories\UserRepository;
use Modules\Authentication\Support\PhoneNumberNormalizer;
use Modules\Authorization\Enums\RoleStatusEnum;
use Modules\Authorization\Models\Role;
use Modules\Authorization\Models\UserRole;
use Modules\Authorization\Services\LastAdminGuard;

class UserManagementService
{
    /** Roles admins may assign when creating managed users (FR-USR-01). */
    private const CREATABLE_ROLE_CODES = ['student', 'faculty', 'librarian', 'view_only'];

    public function __construct(
        private readonly UserRepository $users,
        private readonly UserProfileRepository $profiles,
        private readonly LastAdminGuard $lastAdmin,
        private readonly PhoneNumberNormalizer $phones,
        private readonly UserPhotoService $photos,
    ) {}

    public function list(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->users->paginate($filters, $perPage);
    }

    public function show(int $id): User
    {
        return $this->users->findWithRelations($id)
            ?? throw AuthenticationException::userNotFound();
    }

    public function store(array $data, int $assignedBy): User
    {
        $role = Role::query()
            ->whereKey($data['role_id'])
            ->whereIn('code', self::CREATABLE_ROLE_CODES)
            ->where('status', RoleStatusEnum::Active->value)
            ->first();

        if (! $role) {
            throw AuthenticationException::unprocessable(
                'Admins may only create users with an active student, faculty, librarian, or view-only role.',
            );
        }

        $data = $this->normalizeCredentialPayload($data);
        $this->assertUniqueCredentials($data);
        $profileData = $this->extractProfilePayload($data, createRole: $role);

        return DB::transaction(function () use ($data, $assignedBy, $role, $profileData) {
            $user = $this->users->create([
                'username' => $data['username'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'is_blocked' => $data['is_blocked'] ?? false,
                'is_active' => $data['is_active'] ?? true,
                'locale' => $data['locale'] ?? 'en',
                'break_glass' => $data['break_glass'] ?? false,
            ]);

            $this->profiles->createForUser($user->id, [
                ...$profileData,
                'source' => 'manual',
            ]);

            UserRole::query()->create([
                'user_id' => $user->id,
                'role_id' => $role->id,
                'assigned_by' => $assignedBy,
                'assigned_at' => now(),
                'expires_at' => $data['expires_at'] ?? null,
            ]);

            $created = $this->show($user->id);

            AdminActivity::log(
                'System user created',
                $created,
                [
                    'role_id' => $role->id,
                    'role_code' => $role->code,
                ],
                event: 'user.created',
            );

            return $created;
        });
    }

    public function update(int $id, array $data): User
    {
        $user = $this->show($id);

        $data = $this->normalizeCredentialPayload($data);

        return DB::transaction(function () use ($user, $data) {
            $userData = collect($data)->only([
                'username',
                'email',
                'phone',
                'password',
                'is_blocked',
                'is_active',
                'locale',
                'break_glass',
                'deactivated_reason',
                'locked_until',
            ])->all();

            $profileData = $this->extractProfilePayload($data, existing: $user);

            if (($data['is_active'] ?? null) === false || ($data['is_blocked'] ?? false) === true) {
                $this->lastAdmin->assertCanBlockOrDeactivate($user);
            }

            if ($userData !== []) {
                $this->users->update($user, $userData);
            }

            if ($profileData !== []) {
                $this->profiles->updateOrCreateForUser($user->id, $profileData);
            }

            if (($data['is_blocked'] ?? false) === true || ($data['is_active'] ?? null) === false) {
                $user->tokens()->delete();
            }

            $updated = $this->show($user->id);

            AdminActivity::log(
                'System user updated',
                $updated,
                ['changed' => array_keys($data)],
                event: 'user.updated',
            );

            return $updated;
        });
    }

    public function setBlocked(int $id, bool $isBlocked): User
    {
        $user = $this->show($id);

        if ($isBlocked) {
            $this->lastAdmin->assertCanBlockOrDeactivate($user);
        }

        $this->users->update($user, ['is_blocked' => $isBlocked]);

        if ($isBlocked) {
            $user->tokens()->delete();
        }

        $updated = $this->show($user->id);

        AdminActivity::log(
            $isBlocked ? 'User blocked from system usage' : 'User unblocked',
            $updated,
            ['is_blocked' => $isBlocked],
            event: $isBlocked ? 'blocked' : 'unblocked',
        );

        return $updated;
    }

    /**
     * FR-USR-05: deactivate / reactivate (never hard-delete).
     */
    public function setActive(int $id, bool $active, ?string $reason = null): User
    {
        $user = $this->show($id);

        if (! $active) {
            $this->lastAdmin->assertCanBlockOrDeactivate($user);

            if (! filled($reason)) {
                throw AuthenticationException::unprocessable('A reason is required when deactivating a user.');
            }
        }

        $this->users->update($user, [
            'is_active' => $active,
            'deactivated_reason' => $active ? null : $reason,
        ]);

        if (! $active) {
            $user->tokens()->delete();
        }

        $updated = $this->show($user->id);

        AdminActivity::log(
            $active ? 'User reactivated' : 'User deactivated',
            $updated,
            [
                'is_active' => $active,
                'reason' => $reason,
            ],
            event: $active ? 'user.updated' : 'user.deactivated',
        );

        return $updated;
    }

    public function storePhoto(int $id, UploadedFile $photo): User
    {
        $user = $this->show($id);

        return DB::transaction(function () use ($user, $photo) {
            $paths = $this->photos->storeForUser($user, $photo);
            $this->profiles->updateOrCreateForUser($user->id, $paths);

            $updated = $this->show($user->id);

            AdminActivity::log(
                'User photo updated',
                $updated,
                ['photo_path' => $paths['photo_path']],
                event: 'user.updated',
            );

            return $updated;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeCredentialPayload(array $data): array
    {
        if (array_key_exists('phone', $data)) {
            $data['phone'] = $this->phones->normalize($data['phone'] ?? null);

            if ($data['phone'] !== null && ! $this->phones->isE164($data['phone'])) {
                throw AuthenticationException::unprocessable(
                    'The phone must be a valid phone number (E.164).',
                );
            }
        }

        if (array_key_exists('email', $data) && is_string($data['email'])) {
            $data['email'] = strtolower(trim($data['email']));
        }

        if (array_key_exists('username', $data) && is_string($data['username'])) {
            $data['username'] = trim($data['username']);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function extractProfilePayload(array $data, ?Role $createRole = null, ?User $existing = null): array
    {
        $keys = [
            'first_name',
            'middle_name',
            'last_name',
            'fullname',
            'gender',
            'dob',
            'faculty_id',
            'department_id',
            'programme_id',
            'year_of_study',
        ];
        $profile = [];

        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $profile[$key] = $data[$key];
            }
        }

        if ($profile === []) {
            return [];
        }

        $profile = $this->syncFullnameFromParts($profile);
        $profile = $this->constrainYearOfStudy($profile, $createRole, $existing);

        return $this->assertAcademicHierarchy($profile);
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>
     */
    private function syncFullnameFromParts(array $profile): array
    {
        $hasParts = array_key_exists('first_name', $profile)
            || array_key_exists('middle_name', $profile)
            || array_key_exists('last_name', $profile);

        if (! $hasParts) {
            return $profile;
        }

        $parts = array_filter([
            $profile['first_name'] ?? null,
            $profile['middle_name'] ?? null,
            $profile['last_name'] ?? null,
        ], fn ($part) => filled($part));

        if ($parts !== []) {
            $profile['fullname'] = implode(' ', $parts);
        }

        return $profile;
    }

    /**
     * Year of study is Member-only (student role).
     *
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>
     */
    private function constrainYearOfStudy(array $profile, ?Role $createRole, ?User $existing): array
    {
        if (! array_key_exists('year_of_study', $profile)) {
            return $profile;
        }

        $isStudent = $createRole?->code === 'student'
            || ($existing?->hasActiveRole('student') ?? false);

        if (! $isStudent) {
            $profile['year_of_study'] = null;
        }

        return $profile;
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>
     */
    private function assertAcademicHierarchy(array $profile): array
    {
        $facultyId = $profile['faculty_id'] ?? null;
        $departmentId = $profile['department_id'] ?? null;
        $programmeId = $profile['programme_id'] ?? null;

        if ($departmentId !== null) {
            $department = Department::query()->find($departmentId)
                ?? throw AuthenticationException::unprocessable('Department not found.');

            if ($facultyId !== null && (int) $department->faculty_id !== (int) $facultyId) {
                throw AuthenticationException::unprocessable(
                    'Department does not belong to the selected faculty.',
                );
            }

            if ($facultyId === null) {
                $profile['faculty_id'] = $department->faculty_id;
            }
        }

        if ($programmeId !== null) {
            $programme = Programme::query()->with('department')->find($programmeId)
                ?? throw AuthenticationException::unprocessable('Programme not found.');

            if ($departmentId !== null && (int) $programme->department_id !== (int) $departmentId) {
                throw AuthenticationException::unprocessable(
                    'Programme does not belong to the selected department.',
                );
            }

            $programmeFacultyId = $programme->department?->faculty_id;
            if ($facultyId !== null && $programmeFacultyId !== null && (int) $programmeFacultyId !== (int) $facultyId) {
                throw AuthenticationException::unprocessable(
                    'Programme does not belong to the selected faculty.',
                );
            }

            if ($departmentId === null) {
                $profile['department_id'] = $programme->department_id;
            }

            if (($profile['faculty_id'] ?? null) === null && $programmeFacultyId !== null) {
                $profile['faculty_id'] = $programmeFacultyId;
            }
        }

        return $profile;
    }

    private function assertUniqueCredentials(array $data): void
    {
        if ($this->users->existsByUsername($data['username'])) {
            throw AuthenticationException::duplicateAttribute('username');
        }

        if ($this->users->existsByEmail($data['email'])) {
            throw AuthenticationException::duplicateAttribute('email');
        }

        if (! empty($data['phone']) && $this->users->existsByPhone($data['phone'])) {
            throw AuthenticationException::duplicateAttribute('phone');
        }
    }
}
