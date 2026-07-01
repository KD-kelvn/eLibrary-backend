<?php

namespace Modules\Authorization\Models;

use App\Models\BaseModelWithAudits;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Authentication\Models\User;

class RevokedRole extends BaseModelWithAudits
{
    protected $fillable = [
        'user_role_id',
        'revoked_by',
        'revoked_at',
        'reason',
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
            'revoked_at' => 'datetime',
        ];
    }

    public function userRole(): BelongsTo
    {
        return $this->belongsTo(UserRole::class, 'user_role_id', 'id');
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by', 'id');
    }
}
