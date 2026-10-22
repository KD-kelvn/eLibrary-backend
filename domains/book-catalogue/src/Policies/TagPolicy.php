<?php

namespace Modules\BookCatalogue\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Authentication\Models\User;
use Modules\BookCatalogue\Models\Tag;
use Modules\BookCatalogue\Repositories\Reusables\TagRepository;

class TagPolicy
{
    use HandlesAuthorization;

    public function __construct(protected TagRepository $repository) {}

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Tag $tag): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasActiveRole('admin', 'librarian');
    }

    public function update(User $user, Tag $tag): bool
    {
        return $user->hasActiveRole('admin', 'librarian');
    }

    public function delete(User $user, Tag $tag): bool
    {
        if (! $user->hasActiveRole('admin', 'librarian')) {
            return false;
        }

        return $this->repository->countBooks($tag) === 0;
    }
}
