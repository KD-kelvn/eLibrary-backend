<?php

namespace Modules\BookBorrowing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\BookBorrowing\Exceptions\BookBorrowingException;
use Modules\BookBorrowing\Http\Requests\IndexBorrowingRequestRequest;
use Modules\BookBorrowing\Http\Requests\TransitionBorrowingRequestRequest;
use Modules\BookBorrowing\Http\Resources\BorrowingRequestResource;
use Modules\BookBorrowing\Models\BorrowingRequest;
use Modules\BookBorrowing\Services\BorrowingRequestService;
use Modules\BookBorrowing\Traits\HandlesApiResponses;

class BorrowingRequestController extends Controller
{
    use HandlesApiResponses;

    public function __construct(private readonly BorrowingRequestService $service) {}

    public function index(IndexBorrowingRequestRequest $request): JsonResponse
    {
        try {
            $paginator = $this->service->list(
                $request->validated(),
                (int) $request->input('per_page', 15),
            );

            return $this->paginatedResponse(
                $paginator,
                BorrowingRequestResource::class,
                'Borrowing requests retrieved.',
            );
        } catch (BookBorrowingException $exception) {
            return $this->handleBookBorrowingException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function show(string $borrowingRequest): JsonResponse
    {
        try {
            $model = $this->service->show((int) $borrowingRequest);
            $this->authorize('view', $model);

            return $this->resourceResponse(
                BorrowingRequestResource::make($model),
                'Borrowing request retrieved.',
            );
        } catch (BookBorrowingException $exception) {
            return $this->handleBookBorrowingException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function approve(TransitionBorrowingRequestRequest $request, string $borrowingRequest): JsonResponse
    {
        try {
            $model = BorrowingRequest::query()->with('latestProgress')->findOrFail($borrowingRequest);
            $this->authorize('approve', $model);

            $updated = $this->service->approve(
                (int) $borrowingRequest,
                $request->validated('remarks'),
                $request->validated('start_date'),
                $request->validated('end_date'),
            );

            return $this->resourceResponse(
                BorrowingRequestResource::make($updated),
                'Borrowing request approved.',
            );
        } catch (BookBorrowingException $exception) {
            return $this->handleBookBorrowingException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function reject(TransitionBorrowingRequestRequest $request, string $borrowingRequest): JsonResponse
    {
        try {
            $model = BorrowingRequest::query()->with('latestProgress')->findOrFail($borrowingRequest);
            $this->authorize('reject', $model);

            $updated = $this->service->reject(
                (int) $borrowingRequest,
                $request->validated('remarks'),
            );

            return $this->resourceResponse(
                BorrowingRequestResource::make($updated),
                'Borrowing request rejected.',
            );
        } catch (BookBorrowingException $exception) {
            return $this->handleBookBorrowingException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function markReturned(TransitionBorrowingRequestRequest $request, string $borrowingRequest): JsonResponse
    {
        try {
            $model = BorrowingRequest::query()->with('latestProgress')->findOrFail($borrowingRequest);
            $this->authorize('markReturned', $model);

            $updated = $this->service->markReturned(
                (int) $borrowingRequest,
                $request->validated('remarks'),
            );

            return $this->resourceResponse(
                BorrowingRequestResource::make($updated),
                'Book marked as returned.',
            );
        } catch (BookBorrowingException $exception) {
            return $this->handleBookBorrowingException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function destroy(string $borrowingRequest): JsonResponse
    {
        try {
            $model = BorrowingRequest::query()->with('latestProgress')->findOrFail($borrowingRequest);
            $this->authorize('delete', $model);

            $this->service->destroy((int) $borrowingRequest);

            return $this->successResponse(null, 'Borrowing request deleted.');
        } catch (BookBorrowingException $exception) {
            return $this->handleBookBorrowingException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }
}
