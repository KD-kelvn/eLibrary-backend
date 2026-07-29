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
        return true;
    }

    public function update(User $user, BookDetail $bookDetail): bool
    {
        return true;
    }

    public function delete(User $user, BookDetail $bookDetail): bool
    {
        return $this->repository->countReadingHistories($bookDetail) === 0;
    }
}
