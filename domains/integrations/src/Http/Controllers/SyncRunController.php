<?php

namespace Modules\Integrations\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Integrations\Exceptions\IntegrationsException;
use Modules\Integrations\Http\Requests\IndexSyncRunRequest;
use Modules\Integrations\Http\Requests\ShowSyncRunRequest;
use Modules\Integrations\Http\Requests\StoreSyncRunRequest;
use Modules\Integrations\Http\Resources\SyncRunResource;
use Modules\Integrations\Services\SyncPullService;
use Modules\Integrations\Services\SyncRunQueryService;

class SyncRunController extends Controller
{
    public function __construct(
        private readonly SyncRunQueryService $runs,
        private readonly SyncPullService $pull,
    ) {}

    public function index(IndexSyncRunRequest $request): JsonResponse
    {
        $paginator = $this->runs->paginate($request->validated());

        return $this->successResponse([
            'items' => SyncRunResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_successful_sync_at' => $this->pull->lastSuccessfulAt(),
                'pull_cursor' => $this->pull->lastSuccessfulCursor(),
            ],
        ], 'Sync runs retrieved.');
    }

    public function store(StoreSyncRunRequest $request): JsonResponse
    {
        try {
            $result = $this->pull->queuePull(triggeredBy: (int) $request->user()->id);

            return $this->successResponse($result, 'Pull sync queued.', 202);
        } catch (IntegrationsException $e) {
            return $this->failResponse(null, $e->getMessage(), $e->getCode() ?: 422);
        }
    }

    public function show(ShowSyncRunRequest $request, string $syncRun): JsonResponse
    {
        try {
            $run = $this->runs->findOrFail((int) $syncRun);

            return $this->successResponse(
                new SyncRunResource($run),
                'Sync run retrieved.',
            );
        } catch (IntegrationsException $e) {
            return $this->failResponse(null, $e->getMessage(), $e->getCode() ?: 404);
        }
    }
}
