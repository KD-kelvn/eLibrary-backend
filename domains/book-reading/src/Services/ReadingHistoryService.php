<?php

namespace Modules\BookReading\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\BookReading\Models\ReadingHistory;
use Modules\BookReading\Repositories\ReadingHistoryRepository;

class ReadingHistoryService
{
    public function __construct(private readonly ReadingHistoryRepository $repository) {}

    public function list(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function show(int $id): ReadingHistory
    {
        return $this->repository->find($id) ?? abort(404, 'Reading history not found.');
    }

    public function track(int $userId, array $data): ReadingHistory
    {
        $history = isset($data['history_id'])
            ? ReadingHistory::query()
                ->whereKey($data['history_id'])
                ->where('user_id', $userId)
                ->firstOrFail()
            : ReadingHistory::query()->create([
                'user_id' => $userId,
                'book_detail_id' => $data['book_detail_id'],
                'started_at' => now(),
            ]);

        $history->update([
            'progress_percent' => $data['progress_percent'],
            'current_location' => $data['current_location'] ?? $history->current_location,
            'duration_seconds' => $history->duration_seconds + ($data['duration_seconds'] ?? 0),
            'last_read_at' => now(),
            'completed_at' => ($data['completed'] ?? false)
                ? ($history->completed_at ?? now())
                : $history->completed_at,
            'device' => $data['device'] ?? $history->device,
        ]);

        return $history->load(['user.profile', 'bookDetail']);
    }
}
