<?php

namespace Modules\Integrations\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Integrations\Exceptions\IntegrationsException;
use Modules\Integrations\Http\Requests\IndexSyncRunRequest;
use Modules\Integrations\Http\Requests\ShowSyncRunRequest;
use Modules\Integrations\Http\Resources\SyncRunResource;
use Modules\Integrations\Services\SyncRunQueryService;

class SyncRunController extends Controller
{
    public function __construct(private readonly SyncRunQueryService $runs) {}

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
            ],
        ], 'Sync runs retrieved.');
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
