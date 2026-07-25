<?php

namespace Modules\Authorization\Models;

use App\Models\BaseModal;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemActionRole extends BaseModal
{
    protected $table = 'auth.system_action_roles';

    protected $fillable = ['system_action_id', 'role_id'];

    public function systemAction(): BelongsTo
    {
        return $this->belongsTo(SystemAction::class, 'system_action_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}
