<?php

namespace Modules\Authorization\Policies;

use Modules\Authentication\Models\User;
use Modules\Authorization\Models\Menu;

class MenuPolicy
{
    public function viewAny(User $user): bool { return $user->hasActiveRole('admin'); }
    public function view(User $user, Menu $menu): bool { return $user->hasActiveRole('admin'); }
    public function create(User $user): bool { return $user->hasActiveRole('admin'); }
    public function update(User $user, Menu $menu): bool { return $user->hasActiveRole('admin'); }
    public function delete(User $user, Menu $menu): bool { return $user->hasActiveRole('admin'); }
}
