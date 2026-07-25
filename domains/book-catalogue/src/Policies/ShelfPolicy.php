<?php

namespace Modules\BookCatalogue\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Authentication\Models\User;
use Modules\BookCatalogue\Models\Shelf;
use Modules\BookCatalogue\Repositories\Reusables\ShelfRepository;

class ShelfPolicy
{
    use HandlesAuthorization;

    public function __construct(protected ShelfRepository $repository) {}

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Shelf $shelf): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Shelf $shelf): bool
    {
        return true;
    }

    public function delete(User $user, Shelf $shelf): bool
    {
        return $this->repository->countPhysicalBooks($shelf) === 0;
    }
}
