<?php

namespace Modules\Authorization\Policies;

use Modules\Authentication\Models\User;
use Modules\Authorization\Models\MenuItem;

class MenuItemPolicy
{
    public function viewAny(User $user): bool { return $user->hasActiveRole('admin'); }
    public function view(User $user, MenuItem $item): bool { return $user->hasActiveRole('admin'); }
    public function create(User $user): bool { return $user->hasActiveRole('admin'); }
    public function update(User $user, MenuItem $item): bool { return $user->hasActiveRole('admin'); }
    public function delete(User $user, MenuItem $item): bool { return $user->hasActiveRole('admin'); }
}
