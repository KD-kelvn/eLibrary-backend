<?php

namespace Modules\BookBorrowing\Policies;

use Modules\Authentication\Models\User;
use Modules\BookBorrowing\Enums\BorrowingProcessCodeEnum;
use Modules\BookBorrowing\Models\BorrowingRequest;

class BorrowingRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasActiveRole('admin', 'librarian');
    }

    public function view(User $user, BorrowingRequest $request): bool
    {
        return $user->hasActiveRole('admin', 'librarian')
            || $request->user_id === $user->id;
    }

    public function approve(User $user, BorrowingRequest $request): bool
    {
        return $user->hasActiveRole('librarian', 'admin');
    }

    public function reject(User $user, BorrowingRequest $request): bool
    {
        return $user->hasActiveRole('librarian', 'admin');
    }

    public function markReturned(User $user, BorrowingRequest $request): bool
    {
        return $user->hasActiveRole('librarian', 'admin');
    }

    public function delete(User $user, BorrowingRequest $request): bool
    {
        if (! $user->hasActiveRole('librarian', 'admin')) {
            return false;
        }

        $status = $request->latestProgress?->status_code;

        return in_array($status, [
            BorrowingProcessCodeEnum::Created->value,
            BorrowingProcessCodeEnum::Rejected->value,
        ], true);
    }
}
