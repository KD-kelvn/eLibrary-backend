<?php

namespace Modules\BookBorrowing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\BookBorrowing\Exceptions\BookBorrowingException;
use Modules\BookBorrowing\Http\Requests\IndexBorrowingProcessRequest;
use Modules\BookBorrowing\Http\Requests\StoreBorrowingProcessRequest;
use Modules\BookBorrowing\Http\Requests\UpdateBorrowingProcessRequest;
use Modules\BookBorrowing\Http\Resources\BorrowingProcessResource;
use Modules\BookBorrowing\Models\BorrowingProcess;
use Modules\BookBorrowing\Services\BorrowingProcessService;
use Modules\BookBorrowing\Traits\HandlesApiResponses;

class BorrowingProcessController extends Controller
{
    use HandlesApiResponses;

    public function __construct(private readonly BorrowingProcessService $service) {}

    public function index(IndexBorrowingProcessRequest $request): JsonResponse
    {
        try {
            $paginator = $this->service->list(
                $request->validated(),
                (int) $request->input('per_page', 15),
            );

            return $this->paginatedResponse($paginator, BorrowingProcessResource::class, 'Borrowing processes retrieved.');
        } catch (BookBorrowingException $exception) {
            return $this->handleBookBorrowingException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function options(): JsonResponse
    {
        try {
            $this->authorize('viewAny', BorrowingProcess::class);

            return $this->successResponse(
                BorrowingProcessResource::collection($this->service->options()),
                'Borrowing process options retrieved.',
            );
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function show(string $borrowingProcess): JsonResponse
    {
        try {
            $model = $this->service->show((int) $borrowingProcess);
            $this->authorize('view', $model);

            return $this->resourceResponse(
                BorrowingProcessResource::make($model),
                'Borrowing process retrieved.',
            );
        } catch (BookBorrowingException $exception) {
            return $this->handleBookBorrowingException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function store(StoreBorrowingProcessRequest $request): JsonResponse
    {
        try {
            $process = $this->service->store($request->validated());

            return $this->resourceResponse(
                BorrowingProcessResource::make($process->load(['senderRole', 'receiverRole'])),
                'Borrowing process created.',
                201,
            );
        } catch (BookBorrowingException $exception) {
            return $this->handleBookBorrowingException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function update(UpdateBorrowingProcessRequest $request, string $borrowingProcess): JsonResponse
    {
        try {
            $updated = $this->service->update((int) $borrowingProcess, $request->validated());

            return $this->resourceResponse(
                BorrowingProcessResource::make($updated),
                'Borrowing process updated.',
            );
        } catch (BookBorrowingException $exception) {
            return $this->handleBookBorrowingException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function destroy(string $borrowingProcess): JsonResponse
    {
        try {
            $model = BorrowingProcess::query()->findOrFail($borrowingProcess);
            $this->authorize('delete', $model);

            $this->service->destroy((int) $borrowingProcess);

            return $this->successResponse(null, 'Borrowing process deleted.');
        } catch (BookBorrowingException $exception) {
            return $this->handleBookBorrowingException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }
}
