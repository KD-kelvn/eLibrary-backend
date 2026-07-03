<?php

namespace Modules\Authorization\Models;

use App\Models\BaseModelWithAudits;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SystemPage extends BaseModelWithAudits
{
    protected $table = 'auth.system_pages';

    protected $fillable = [
        'name',
        'description',
        'code',
        'url',
        'is_public',
    ];

    public function systemPageRoles(): HasMany
    {
        return $this->hasMany(SystemPageRole::class, 'system_page_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, SystemPageRole::class, 'system_page_id', 'role_id');
    }
}
