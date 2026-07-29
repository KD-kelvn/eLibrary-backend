<?php

namespace Modules\BookBorrowing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\BookBorrowing\Exceptions\BookBorrowingException;
use Modules\BookBorrowing\Http\Requests\IndexPenaltyPolicyRequest;
use Modules\BookBorrowing\Http\Requests\StorePenaltyPolicyRequest;
use Modules\BookBorrowing\Http\Requests\UpdatePenaltyPolicyRequest;
use Modules\BookBorrowing\Http\Resources\PenaltyPolicyResource;
use Modules\BookBorrowing\Models\PenaltyPolicy;
use Modules\BookBorrowing\Services\PenaltyPolicyService;
use Modules\BookBorrowing\Traits\HandlesApiResponses;

class PenaltyPolicyController extends Controller
{
    use HandlesApiResponses;

    public function __construct(private readonly PenaltyPolicyService $service) {}

    public function index(IndexPenaltyPolicyRequest $request): JsonResponse
    {
        try {
            $paginator = $this->service->list(
                $request->validated(),
                (int) $request->input('per_page', 15),
            );

            return $this->paginatedResponse($paginator, PenaltyPolicyResource::class, 'Penalty policies retrieved.');
        } catch (BookBorrowingException $exception) {
            return $this->handleBookBorrowingException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function show(string $penaltyPolicy): JsonResponse
    {
        try {
            $model = $this->service->show((int) $penaltyPolicy);
            $this->authorize('view', $model);

            return $this->resourceResponse(
                PenaltyPolicyResource::make($model),
                'Penalty policy retrieved.',
            );
        } catch (BookBorrowingException $exception) {
            return $this->handleBookBorrowingException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function store(StorePenaltyPolicyRequest $request): JsonResponse
    {
        try {
            $policy = $this->service->store($request->validated());

            return $this->resourceResponse(
                PenaltyPolicyResource::make($policy),
                'Penalty policy created.',
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

    public function update(UpdatePenaltyPolicyRequest $request, string $penaltyPolicy): JsonResponse
    {
        try {
            $updated = $this->service->update((int) $penaltyPolicy, $request->validated());

            return $this->resourceResponse(
                PenaltyPolicyResource::make($updated),
                'Penalty policy updated.',
            );
        } catch (BookBorrowingException $exception) {
            return $this->handleBookBorrowingException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function destroy(string $penaltyPolicy): JsonResponse
    {
        try {
            $model = PenaltyPolicy::query()->findOrFail($penaltyPolicy);
            $this->authorize('delete', $model);

            $this->service->destroy((int) $penaltyPolicy);

            return $this->successResponse(null, 'Penalty policy deleted.');
        } catch (BookBorrowingException $exception) {
            return $this->handleBookBorrowingException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }
}
