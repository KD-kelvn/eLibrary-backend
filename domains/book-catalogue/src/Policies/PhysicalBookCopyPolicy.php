<?php

namespace Modules\BookCatalogue\Policies;

use Modules\Authentication\Models\User;
use Modules\BookCatalogue\Models\PhysicalBookCopy;

class PhysicalBookCopyPolicy
{
    public function updateStatus(User $user, PhysicalBookCopy $copy): bool
    {
        return $user->hasActiveRole('admin', 'librarian');
    }
}
