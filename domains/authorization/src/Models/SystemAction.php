<?php

namespace Modules\Authorization\Models;

use App\Models\BaseModelWithAudits;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class SystemAction extends BaseModelWithAudits
{
    protected $fillable = [
        'role_id',
        'name',
        'description',
        'code',
        'action_type',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function getTable(): string
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            ? 'auth.system_actions'
            : 'system_actions';
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function systemActionRoles(): HasMany
    {
        return $this->hasMany(SystemActionRole::class, 'system_action_id');
    }

    public function userGrants(): HasMany
    {
        return $this->hasMany(UserSystemAction::class, 'system_action_id');
    }

    public function roles(): BelongsToMany
    {
        $pivot = Schema::getConnection()->getDriverName() === 'pgsql'
            ? 'auth.system_action_roles'
            : 'system_action_roles';

        return $this->belongsToMany(
            Role::class,
            $pivot,
            'system_action_id',
            'role_id',
        );
    }
}
