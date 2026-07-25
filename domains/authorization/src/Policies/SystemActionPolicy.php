<?php

namespace Modules\Authorization\Policies;

use Modules\Authentication\Models\User;
use Modules\Authorization\Models\SystemAction;

class SystemActionPolicy
{
    public function viewAny(User $user): bool { return $user->hasActiveRole('admin'); }
    public function view(User $user, SystemAction $action): bool { return $user->hasActiveRole('admin'); }
    public function create(User $user): bool { return $user->hasActiveRole('admin'); }
    public function update(User $user, SystemAction $action): bool { return $user->hasActiveRole('admin'); }
    public function delete(User $user, SystemAction $action): bool { return $user->hasActiveRole('admin'); }
}
