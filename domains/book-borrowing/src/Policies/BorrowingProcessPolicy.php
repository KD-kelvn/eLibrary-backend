<?php

namespace Modules\BookBorrowing\Policies;

use Modules\Authentication\Models\User;
use Modules\BookBorrowing\Models\BorrowingProcess;

class BorrowingProcessPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasActiveRole('admin', 'librarian');
    }

    public function view(User $user, BorrowingProcess $process): bool
    {
        return $user->hasActiveRole('admin', 'librarian');
    }

    public function create(User $user): bool
    {
        return $user->hasActiveRole('admin');
    }

    public function update(User $user, BorrowingProcess $process): bool
    {
        return $user->hasActiveRole('admin');
    }

    public function delete(User $user, BorrowingProcess $process): bool
    {
        return $user->hasActiveRole('admin');
    }
}
