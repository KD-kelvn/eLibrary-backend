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

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    public function systemPageRoles(): HasMany
    {
        return $this->hasMany(SystemPageRole::class, 'system_page_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'auth.system_page_roles',
            'system_page_id',
            'role_id',
        );
    }
}
