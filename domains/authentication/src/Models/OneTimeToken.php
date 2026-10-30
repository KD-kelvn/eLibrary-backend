<?php

namespace Modules\Authentication\Models;

use App\Models\BaseModelWithAudits;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class OneTimeToken extends BaseModelWithAudits
{
    protected $fillable = [
        'user_id',
        'challenge_id',
        'token',
        'via',
        'expires_at',
        'purpose',
        'ip_address',
        'user_agent',
        'used_at',
        'attempts',
        'max_attempts',
        'code_length',
        'expiry_minutes',
    ];

    public function getTable(): string
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            ? 'auth.one_time_tokens'
            : 'one_time_tokens';
    }

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'attempts' => 'integer',
            'max_attempts' => 'integer',
            'code_length' => 'integer',
            'expiry_minutes' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }
}
