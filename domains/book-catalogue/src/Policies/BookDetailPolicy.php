<?php

namespace Modules\BookCatalogue\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Authentication\Models\User;
use Modules\BookCatalogue\Models\BookDetail;
use Modules\BookCatalogue\Repositories\BookDetailRepository;

class BookDetailPolicy
{
    use HandlesAuthorization;

    public function __construct(protected BookDetailRepository $repository) {}

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, BookDetail $bookDetail): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasActiveRole('admin', 'librarian');
    }

    public function update(User $user, BookDetail $bookDetail): bool
    {
        return $user->hasActiveRole('admin', 'librarian');
    }

    public function delete(User $user, BookDetail $bookDetail): bool
    {
        if (! $user->hasActiveRole('admin', 'librarian')) {
            return false;
        }

        return $this->repository->countReadingHistories($bookDetail) === 0;
    }
}
