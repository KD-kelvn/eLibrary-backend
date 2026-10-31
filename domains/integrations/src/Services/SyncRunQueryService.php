<?php

namespace Modules\Integrations\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Integrations\Exceptions\IntegrationsException;
use Modules\Integrations\Models\SyncRun;

class SyncRunQueryService
{
    /**
     * @param  array{mode?: string, status?: string, per_page?: int}  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = SyncRun::query()->orderByDesc('id');

        if (filled($filters['mode'] ?? null)) {
            $query->where('mode', $filters['mode']);
        }

        if (filled($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate((int) ($filters['per_page'] ?? 20));
    }

    public function findOrFail(int $id): SyncRun
    {
        $run = SyncRun::query()->with(['items' => fn ($q) => $q->orderBy('id')])->find($id);

        if (! $run) {
            throw IntegrationsException::unprocessable('Sync run not found.', 404);
        }

        return $run;
    }
}
