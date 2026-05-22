<?php

namespace Modules\Authentication\Services;

use Illuminate\Support\Facades\DB;
use Modules\Authentication\Exceptions\AuthenticationException;
use Modules\Authentication\Repositories\UserProfileRepository;
use Modules\Authentication\Repositories\UserRepository;
use Modules\Authentication\Services\Concerns\IssuesApiTokens;

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
