<?php

namespace Modules\Authorization\Models;

use App\Models\BaseModal;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItemRole extends BaseModal
{
    protected $table = 'auth.menu_item_roles';

    protected $fillable = ['menu_item_id', 'role_id'];

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'menu_item_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}
