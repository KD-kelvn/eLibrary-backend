<?php

namespace Modules\BookBorrowing\Policies;

use Modules\Authentication\Models\User;
use Modules\BookBorrowing\Models\PenaltyBatch;

class PenaltyBatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasActiveRole('admin', 'librarian');
    }

    public function view(User $user, PenaltyBatch $batch): bool
    {
        return $user->hasActiveRole('admin', 'librarian');
    }

    public function stop(User $user, PenaltyBatch $batch): bool
    {
        return $user->hasActiveRole('librarian', 'admin');
    }

    public function markPaid(User $user, PenaltyBatch $batch): bool
    {
        return $user->hasActiveRole('librarian', 'admin');
    }
}
