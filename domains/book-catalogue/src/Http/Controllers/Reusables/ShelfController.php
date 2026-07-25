<?php

namespace Modules\BookCatalogue\Http\Controllers\Reusables;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\BookCatalogue\Exceptions\BookCatalogueException;
use Modules\BookCatalogue\Http\Requests\Reusables\IndexShelfRequest;
use Modules\BookCatalogue\Http\Requests\Reusables\StoreShelfRequest;
use Modules\BookCatalogue\Http\Requests\Reusables\UpdateShelfRequest;
use Modules\BookCatalogue\Http\Resources\ShelfResource;
use Modules\BookCatalogue\Models\Shelf;
use Modules\BookCatalogue\Services\Reusables\ShelfService;
use Modules\BookCatalogue\Traits\HandlesApiResponses;

class ShelfController extends Controller
{
    use HandlesApiResponses;

    public function __construct(private readonly ShelfService $shelfService) {}

    public function index(IndexShelfRequest $request): JsonResponse
    {
        try {
            $paginator = $this->shelfService->list(
                $request->validated(),
                (int) $request->input('per_page', 15),
            );

            return $this->paginatedResponse($paginator, ShelfResource::class, 'Shelves retrieved.');
        } catch (BookCatalogueException $exception) {
            return $this->handleBookCatalogueException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function show(string $shelf): JsonResponse
    {
        try {
            $this->authorize('view', Shelf::query()->findOrFail($shelf));

            return $this->resourceResponse(
                ShelfResource::make($this->shelfService->show((int) $shelf)),
                'Shelf retrieved.',
            );
        } catch (BookCatalogueException $exception) {
            return $this->handleBookCatalogueException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function store(StoreShelfRequest $request): JsonResponse
    {
        try {
            $shelf = $this->shelfService->store($request->validated());

            return $this->resourceResponse(
                ShelfResource::make($shelf),
                'Shelf created.',
                201,
            );
        } catch (BookCatalogueException $exception) {
            return $this->handleBookCatalogueException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function update(UpdateShelfRequest $request, string $shelf): JsonResponse
    {
        try {
            $updated = $this->shelfService->update((int) $shelf, $request->validated());

            return $this->resourceResponse(
                ShelfResource::make($updated),
                'Shelf updated.',
            );
        } catch (BookCatalogueException $exception) {
            return $this->handleBookCatalogueException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function destroy(string $shelf): JsonResponse
    {
        try {
            $model = Shelf::query()->findOrFail($shelf);
            $this->authorize('delete', $model);

            $this->shelfService->destroy((int) $shelf);

            return $this->successResponse(null, 'Shelf deleted.');
        } catch (BookCatalogueException $exception) {
            return $this->handleBookCatalogueException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }
}
