<?php

namespace Modules\BookReading\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\BookReading\Models\ReadingHistory;

class ReadingHistoryRepository
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return ReadingHistory::query()
            ->with(['user.profile', 'bookDetail'])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $search = $filters['search'];
                $query->where(fn ($builder) => $builder
                    ->whereHas('user', fn ($user) => $user
                        ->where('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('profile', fn ($profile) => $profile
                            ->where('fullname', 'like', "%{$search}%")))
                    ->orWhereHas('bookDetail', fn ($book) => $book
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('isbn', 'like', "%{$search}%")));
            })
            ->when(
                filled($filters['type'] ?? null),
                fn ($query) => $query->whereHas(
                    'bookDetail',
                    fn ($book) => $book->where('type_code', strtoupper($filters['type'])),
                ),
            )
            ->when(
                filled($filters['days'] ?? null),
                fn ($query) => $query->where('last_read_at', '>=', now()->subDays((int) $filters['days'])),
            )
            ->latest('last_read_at')
            ->paginate($perPage);
    }

    public function find(int $id): ?ReadingHistory
    {
        return ReadingHistory::query()->with(['user.profile', 'bookDetail'])->find($id);
    }
}
