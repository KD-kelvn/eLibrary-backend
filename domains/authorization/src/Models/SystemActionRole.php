<?php

namespace Modules\Authorization\Models;

use App\Models\BaseModal;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class SystemActionRole extends BaseModal
{
    protected $fillable = ['system_action_id', 'role_id'];

    public function getTable(): string
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            ? 'auth.system_action_roles'
            : 'system_action_roles';
    }

    public function systemAction(): BelongsTo
    {
        return $this->belongsTo(SystemAction::class, 'system_action_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}
