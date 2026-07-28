<?php

namespace Modules\Authentication\Services;

use App\Support\AdminActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Authentication\Models\PersonalAccessToken;
use Modules\Authentication\Models\User;

class SessionService
{
    /**
     * @param  array{search?: string, status?: string, user_id?: int}  $filters
     */
    public function listAdmin(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = PersonalAccessToken::query()
            ->with(['tokenable.profile'])
            ->where('tokenable_type', (new User)->getMorphClass())
            ->latest('id');

        if (! empty($filters['user_id'])) {
            $query->where('tokenable_id', $filters['user_id']);
        }

        if (($filters['status'] ?? null) === 'active') {
            $query->active();
        } elseif (($filters['status'] ?? null) === 'expired') {
            $query->expired();
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('ip_address', 'ilike', "%{$search}%")
                    ->orWhere('user_agent', 'ilike', "%{$search}%")
                    ->orWhereHasMorph('tokenable', [User::class], function ($userQuery) use ($search) {
                        $userQuery
                            ->where('username', 'ilike', "%{$search}%")
                            ->orWhere('email', 'ilike', "%{$search}%")
                            ->orWhereHas('profile', fn ($profile) => $profile->where('fullname', 'ilike', "%{$search}%"));
                    });
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * @return array{
     *     total: int,
     *     active: int,
     *     expired: int,
     *     activeToday: int,
     *     activeThisWeek: int,
     *     uniqueUsers: int
     * }
     */
    public function summary(): array
    {
        $type = (new User)->getMorphClass();
        $base = fn () => PersonalAccessToken::query()->where('tokenable_type', $type);

        return [
            'total' => $base()->count(),
            'active' => $base()->active()->count(),
            'expired' => $base()->expired()->count(),
            'activeToday' => $base()
                ->active()
                ->where('last_used_at', '>=', now()->startOfDay())
                ->count(),
            'activeThisWeek' => $base()
                ->active()
                ->where('last_used_at', '>=', now()->startOfWeek())
                ->count(),
            'uniqueUsers' => (int) $base()
                ->active()
                ->distinct()
                ->count('tokenable_id'),
        ];
    }

    /**
     * @return Collection<int, PersonalAccessToken>
     */
    public function listForUser(User $user): Collection
    {
        return PersonalAccessToken::query()
            ->where('tokenable_type', $user->getMorphClass())
            ->where('tokenable_id', $user->id)
            ->latest('id')
            ->get();
    }

    public function revoke(PersonalAccessToken $token, ?User $actor = null): void
    {
        $token->loadMissing('tokenable');
        $token->delete();

        if ($actor?->hasActiveRole('admin')) {
            AdminActivity::log(
                'Session revoked',
                $token->tokenable instanceof User ? $token->tokenable : null,
                [
                    'token_id' => $token->id,
                    'token_name' => $token->name,
                    'ip_address' => $token->ip_address,
                ],
                event: 'session_revoked',
            );
        }
    }

    public function revokeOthers(User $user, PersonalAccessToken $current): int
    {
        return PersonalAccessToken::query()
            ->where('tokenable_type', $user->getMorphClass())
            ->where('tokenable_id', $user->id)
            ->where('id', '!=', $current->id)
            ->delete();
    }

    public function revokeAllForUser(User $user, ?User $actor = null): int
    {
        $count = PersonalAccessToken::query()
            ->where('tokenable_type', $user->getMorphClass())
            ->where('tokenable_id', $user->id)
            ->delete();

        if ($actor?->hasActiveRole('admin')) {
            AdminActivity::log(
                'All sessions revoked for user',
                $user,
                ['revoked_count' => $count],
                event: 'sessions_revoked',
            );
        }

        return $count;
    }
}
