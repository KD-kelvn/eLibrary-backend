<?php

namespace Modules\Authorization\Models;

use App\Models\BaseModelWithAudits;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Modules\Authorization\Enums\RoleStatusEnum;

class Role extends BaseModelWithAudits
{
    protected $table = 'auth.roles';

    protected $fillable = [
        'name',
        'description',
        'status',
        'code',
        'created_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => RoleStatusEnum::class,
        ];
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class, 'role_id', 'id');
    }

    public function activeUserRoles(): HasMany
    {
        return $this->userRoles()->active();
    }

    public function revokedRoles(): HasManyThrough
    {
        return $this->hasManyThrough(
            RevokedRole::class,
            UserRole::class,
            'role_id',
            'user_role_id',
        );
    }
}
