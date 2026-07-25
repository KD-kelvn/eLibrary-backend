<?php

namespace Modules\BookReading\Policies;

use Modules\Authentication\Models\User;
use Modules\BookReading\Models\ReadingHistory;

class ReadingHistoryPolicy
{
    public function viewAny(User $user): bool { return $user->hasActiveRole('admin'); }
    public function view(User $user, ReadingHistory $history): bool { return $user->hasActiveRole('admin'); }
}
