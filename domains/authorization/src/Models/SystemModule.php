<?php

namespace Modules\Authorization\Models;

use App\Models\BaseModelWithAudits;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SystemModule extends BaseModelWithAudits
{
    protected $table = 'auth.system_modules';

    protected $fillable = [
        'name',
        'description',
        'code',
        'icon_code',
        'bg_color',
        'landing_url',
    ];

    public function systemModuleRoles(): HasMany
    {
        return $this->hasMany(SystemModuleRole::class, 'system_module_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, SystemModuleRole::class, 'system_module_id', 'role_id');
    }
}
