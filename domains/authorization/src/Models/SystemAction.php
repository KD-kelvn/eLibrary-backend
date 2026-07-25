<?php

namespace Modules\Authorization\Models;

use App\Models\BaseModelWithAudits;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SystemAction extends BaseModelWithAudits
{
    protected $table = 'auth.system_actions';

    protected $fillable = [
        'menu_item_id',
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

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'menu_item_id');
    }

    public function systemActionRoles(): HasMany
    {
        return $this->hasMany(SystemActionRole::class, 'system_action_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'auth.system_action_roles',
            'system_action_id',
            'role_id',
        );
    }
}
