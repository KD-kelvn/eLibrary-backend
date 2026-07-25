<?php

namespace Modules\BookReading\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\BookReading\Http\Requests\IndexReadingHistoryRequest;
use Modules\BookReading\Http\Requests\TrackReadingRequest;
use Modules\BookReading\Http\Resources\ReadingHistoryResource;
use Modules\BookReading\Services\ReadingHistoryService;

class ReadingHistoryController extends Controller
{
    public function __construct(private readonly ReadingHistoryService $service) {}

    public function index(IndexReadingHistoryRequest $request): JsonResponse
    {
        $paginator = $this->service->list(
            $request->validated(),
            (int) $request->input('per_page', 15),
        );

        return $this->successResponse([
            'items' => ReadingHistoryResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], 'Reading history retrieved.');
    }

    public function show(string $reading_history): JsonResponse
    {
        $history = $this->service->show((int) $reading_history);
        $this->authorize('view', $history);

        return $this->successResponse(
            ReadingHistoryResource::make($history),
            'Reading history retrieved.',
        );
    }

    public function track(TrackReadingRequest $request): JsonResponse
    {
        $history = $this->service->track(
            (int) $request->user()->id,
            $request->validated(),
        );

        return $this->successResponse(
            ReadingHistoryResource::make($history),
            'Reading progress recorded.',
            201,
        );
    }
}
