<?php

namespace Modules\Authorization\Models;

use App\Models\BaseModal;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemModuleRole extends BaseModal
{
    protected $table = 'auth.system_module_roles';

    protected $fillable = [
        'system_module_id',
        'role_id',
    ];

    public function systemModule(): BelongsTo
    {
        return $this->belongsTo(SystemModule::class, 'system_module_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}
