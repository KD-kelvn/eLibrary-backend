<?php

namespace Modules\Authorization\Models;

use App\Models\BaseModal;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuRole extends BaseModal
{
    protected $table = 'auth.menu_roles';

    protected $fillable = ['menu_id', 'role_id'];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}
