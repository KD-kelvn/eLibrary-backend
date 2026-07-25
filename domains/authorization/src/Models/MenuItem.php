<?php

namespace Modules\Authorization\Models;

use App\Models\BaseModelWithAudits;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends BaseModelWithAudits
{
    protected $table = 'auth.menu_items';

    protected $fillable = [
        'menu_id',
        'name',
        'description',
        'code',
        'route',
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

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }

    public function menuItemRoles(): HasMany
    {
        return $this->hasMany(MenuItemRole::class, 'menu_item_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'auth.menu_item_roles', 'menu_item_id', 'role_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(SystemAction::class, 'menu_item_id');
    }
}
