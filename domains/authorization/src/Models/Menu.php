<?php

namespace Modules\Authorization\Models;

use App\Models\BaseModelWithAudits;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends BaseModelWithAudits
{
    protected $table = 'auth.menus';

    protected $fillable = [
        'name',
        'description',
        'code',
        'icon_code',
        'sort_order',
        'is_public',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_public' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'menu_id')->orderBy('sort_order');
    }

    public function menuRoles(): HasMany
    {
        return $this->hasMany(MenuRole::class, 'menu_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'auth.menu_roles', 'menu_id', 'role_id');
    }
}
