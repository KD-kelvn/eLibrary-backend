<?php

namespace Modules\BookCatalogue\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Authentication\Models\User;
use Modules\BookCatalogue\Models\BookFavourite;

class BookFavouritePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, BookFavourite $favourite): bool
    {
        return (int) $favourite->user_id === (int) $user->id
            || $user->hasActiveRole('admin', 'librarian');
    }

    public function create(User $user): bool
    {
        return $user->hasActiveRole('student', 'admin', 'librarian');
    }

    public function delete(User $user, BookFavourite $favourite): bool
    {
        return (int) $favourite->user_id === (int) $user->id
            || $user->hasActiveRole('admin', 'librarian');
    }
}
