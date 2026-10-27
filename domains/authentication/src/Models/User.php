<?php

namespace Modules\Authentication\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\HasApiTokens;
use Modules\Authorization\Models\UserRole;
use Modules\Authorization\Models\UserSystemAction;
use Modules\BookReading\Models\ReadingHistory;
use Modules\Notifications\Models\DatabaseNotification;
use Yajra\Auditable\AuditableWithDeletesTrait;

class User extends Authenticatable
{
    use AuditableWithDeletesTrait, HasApiTokens, Notifiable, SoftDeletes;

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
        'is_active',
        'deactivated_reason',
        'break_glass',
        'locked_until',
        'locale',
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
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_blocked' => 'boolean',
            'is_active' => 'boolean',
            'break_glass' => 'boolean',
            'locked_until' => 'datetime',
        ];
    }

    public function getTable(): string
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            ? 'auth.users'
            : 'users';
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    /**
     * Account may authenticate (active, not blocked, not lockout).
     */
    public function canAuthenticate(): bool
    {
        return $this->is_active !== false
            && ! $this->is_blocked
            && ! $this->isLocked();
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

    public function userSystemActions(): HasMany
    {
        return $this->hasMany(UserSystemAction::class, 'user_id');
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

    public function notifications(): MorphMany
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')->latest();
    }

    public function routeNotificationForSms(): ?string
    {
        return $this->phone;
    }

    /** @deprecated Prefer routeNotificationForSms() */
    public function routeNotificationForLogSms(): ?string
    {
        return $this->routeNotificationForSms();
    }
}
