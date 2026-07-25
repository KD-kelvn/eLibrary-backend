<?php

namespace Modules\BookCatalogue\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Authentication\Models\User;
use Modules\BookCatalogue\Models\SubCategory;
use Modules\BookCatalogue\Repositories\Reusables\SubCategoryRepository;

class SubCategoryPolicy
{
    use HandlesAuthorization;

    public function __construct(protected SubCategoryRepository $repository) {}

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SubCategory $subCategory): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, SubCategory $subCategory): bool
    {
        return true;
    }

    public function delete(User $user, SubCategory $subCategory): bool
    {
        return $this->repository->countBooks($subCategory) === 0;
    }
}
