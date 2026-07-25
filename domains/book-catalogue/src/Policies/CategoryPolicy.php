<?php

namespace Modules\BookCatalogue\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Authentication\Models\User;
use Modules\BookCatalogue\Models\Category;
use Modules\BookCatalogue\Repositories\Reusables\CategoryRepository;

class CategoryPolicy
{
    use HandlesAuthorization;

    public function __construct(protected CategoryRepository $repository) {}

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Category $category): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Category $category): bool
    {
        return true;
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->repository->countBooks($category) === 0;
    }
}
