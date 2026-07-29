<?php

namespace Modules\BookBorrowing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\BookBorrowing\Exceptions\BookBorrowingException;
use Modules\BookBorrowing\Http\Requests\IndexPenaltyBatchRequest;
use Modules\BookBorrowing\Http\Requests\MarkPenaltyBatchPaidRequest;
use Modules\BookBorrowing\Http\Requests\StopPenaltyBatchRequest;
use Modules\BookBorrowing\Http\Resources\PenaltyBatchResource;
use Modules\BookBorrowing\Services\PenaltyBatchService;
use Modules\BookBorrowing\Traits\HandlesApiResponses;

class PenaltyBatchController extends Controller
{
    use HandlesApiResponses;

    public function __construct(private readonly PenaltyBatchService $service) {}

    public function index(IndexPenaltyBatchRequest $request): JsonResponse
    {
        try {
            $paginator = $this->service->list(
                $request->validated(),
                (int) $request->input('per_page', 15),
            );

            return $this->paginatedResponse(
                $paginator,
                PenaltyBatchResource::class,
                'Penalty batches retrieved.',
            );
        } catch (BookBorrowingException $exception) {
            return $this->handleBookBorrowingException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function show(string $penaltyBatch): JsonResponse
    {
        try {
            $model = $this->service->show((int) $penaltyBatch);
            $this->authorize('view', $model);

            return $this->resourceResponse(
                PenaltyBatchResource::make($model),
                'Penalty batch retrieved.',
            );
        } catch (BookBorrowingException $exception) {
            return $this->handleBookBorrowingException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function stop(StopPenaltyBatchRequest $request, string $penaltyBatch): JsonResponse
    {
        try {
            $updated = $this->service->stop(
                (int) $penaltyBatch,
                $request->validated('stopped_reason'),
            );

            return $this->resourceResponse(
                PenaltyBatchResource::make($updated),
                'Penalty batch stopped.',
            );
        } catch (BookBorrowingException $exception) {
            return $this->handleBookBorrowingException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function markPaid(MarkPenaltyBatchPaidRequest $request, string $penaltyBatch): JsonResponse
    {
        try {
            $updated = $this->service->markPaid(
                (int) $penaltyBatch,
                $request->validated('remarks'),
            );

            return $this->resourceResponse(
                PenaltyBatchResource::make($updated),
                'Penalty batch marked as paid.',
            );
        } catch (BookBorrowingException $exception) {
            return $this->handleBookBorrowingException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }
}
