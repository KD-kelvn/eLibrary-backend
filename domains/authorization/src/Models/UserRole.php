<?php

namespace Modules\Authorization\Models;

use App\Models\BaseModelWithAudits;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Authentication\Models\User;

class UserRole extends BaseModelWithAudits
{
    protected $fillable = [
        'user_id',
        'role_id',
        'assigned_by',
        'expires_at',
        'assigned_at',
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
            'expires_at' => 'date',
            'assigned_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by', 'id');
    }

    public function revokedRole(): HasOne
    {
        return $this->hasOne(RevokedRole::class, 'user_role_id', 'id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where(function (Builder $builder) {
                $builder->whereNull('expires_at')
                    ->orWhereDate('expires_at', '>=', now());
            })
            ->whereDoesntHave('revokedRole');
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where(function (Builder $builder) {
            $builder->whereDate('expires_at', '<', now())
                ->orWhereHas('revokedRole');
        });
    }

    public function isActive(): bool
    {
        if ($this->relationLoaded('revokedRole') && $this->revokedRole !== null) {
            return false;
        }

        if (! $this->relationLoaded('revokedRole')) {
            $this->load('revokedRole');
        }

        if ($this->revokedRole !== null) {
            return false;
        }

        if ($this->expires_at === null) {
            return true;
        }

        return $this->expires_at->greaterThanOrEqualTo(now()->startOfDay());
    }
}
