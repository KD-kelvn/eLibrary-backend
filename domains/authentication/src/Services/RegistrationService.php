<?php

namespace Modules\Authentication\Services;

use Illuminate\Support\Facades\DB;
use Modules\Authentication\Exceptions\AuthenticationException;
use Modules\Authentication\Repositories\UserProfileRepository;
use Modules\Authentication\Repositories\UserRepository;
use Modules\Authentication\Services\Concerns\IssuesApiTokens;
use Modules\Authorization\Models\Role;
use Modules\Authorization\Models\UserRole;

class RegistrationService
{
    use IssuesApiTokens;

    public function __construct(
        private readonly UserRepository $users,
        private readonly UserProfileRepository $profiles,
    ) {}

    /** @param  array<string, mixed>  $data */
    public function register(array $data): array
    {
        $this->assertUniqueCredentials($data);

        $user = DB::transaction(function () use ($data) {
            $user = $this->users->create([
                'username' => $data['username'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
            ]);

            $this->profiles->createForUser($user->id, [
                'fullname' => $data['fullname'],
                'gender' => $data['gender'] ?? null,
                'dob' => $data['dob'] ?? null,
            ]);

            $studentRole = Role::query()
                ->where('code', 'student')
                ->where('status', 'active')
                ->first();

            if ($studentRole) {
                UserRole::query()->create([
                    'user_id' => $user->id,
                    'role_id' => $studentRole->id,
                    'assigned_by' => $user->id,
                    'assigned_at' => now(),
                ]);
            }

            return $user->load('profile');
        });

        return $this->issueToken($user, $data['device_name'] ?? null);
    }

    /** @param  array<string, mixed>  $data */
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
