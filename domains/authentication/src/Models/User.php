<?php

namespace Modules\Authentication\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Authorization\Models\UserRole;
use Modules\BookReading\Models\ReadingHistory;
use Yajra\Auditable\AuditableWithDeletesTrait;

class User extends Authenticatable
{
    use AuditableWithDeletesTrait, HasApiTokens, Notifiable, SoftDeletes;

    protected $table = 'auth.users';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'phone',
        'email',
        'password',
        'remember_token',
        'is_blocked',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_blocked' => 'boolean',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function oneTimeTokens(): HasMany
    {
        return $this->hasMany(OneTimeToken::class);
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class, 'user_id');
    }

    public function activeUserRoles(): HasMany
    {
        return $this->userRoles()->active();
    }

    public function hasActiveRole(string ...$codes): bool
    {
        return $this->activeUserRoles()
            ->whereHas('role', fn ($query) => $query
                ->whereIn('code', $codes)
                ->where('status', 'active'))
            ->exists();
    }

    public function readingHistories(): HasMany
    {
        return $this->hasMany(ReadingHistory::class, 'user_id');
    }
}
