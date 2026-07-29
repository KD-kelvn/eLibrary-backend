<?php

namespace Modules\BookCatalogue\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\BookCatalogue\Exceptions\BookCatalogueException;
use Modules\BookCatalogue\Http\Requests\IndexBookDetailRequest;
use Modules\BookCatalogue\Http\Requests\StoreBookDetailRequest;
use Modules\BookCatalogue\Http\Requests\UpdateBookDetailRequest;
use Modules\BookCatalogue\Http\Resources\BookDetailResource;
use Modules\BookCatalogue\Models\BookDetail;
use Modules\BookCatalogue\Services\BookDetailService;
use Modules\BookCatalogue\Traits\HandlesApiResponses;

class BookDetailController extends Controller
{
    use HandlesApiResponses;

    public function __construct(private readonly BookDetailService $bookDetailService) {}

    public function index(IndexBookDetailRequest $request): JsonResponse
    {
        try {
            $paginator = $this->bookDetailService->list(
                $request->validated(),
                (int) $request->input('per_page', 15),
            );

            return $this->paginatedResponse($paginator, BookDetailResource::class, 'Books retrieved.');
        } catch (BookCatalogueException $exception) {
            return $this->handleBookCatalogueException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function show(string $book): JsonResponse
    {
        try {
            $this->authorize('view', BookDetail::query()->findOrFail($book));

            return $this->resourceResponse(
                BookDetailResource::make($this->bookDetailService->show((int) $book)),
                'Book retrieved.',
            );
        } catch (BookCatalogueException $exception) {
            return $this->handleBookCatalogueException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function store(StoreBookDetailRequest $request): JsonResponse
    {
        try {
            $bookDetail = $this->bookDetailService->store($request->validated());

            return $this->resourceResponse(
                BookDetailResource::make($bookDetail),
                'Book created.',
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

    public function update(UpdateBookDetailRequest $request, string $book): JsonResponse
    {
        try {
            $updated = $this->bookDetailService->update((int) $book, $request->validated());

            return $this->resourceResponse(
                BookDetailResource::make($updated),
                'Book updated.',
            );
        } catch (BookCatalogueException $exception) {
            return $this->handleBookCatalogueException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function destroy(string $book): JsonResponse
    {
        try {
            $model = BookDetail::query()->findOrFail($book);
            $this->authorize('delete', $model);

            $this->bookDetailService->destroy((int) $book);

            return $this->successResponse(null, 'Book deleted.');
        } catch (BookCatalogueException $exception) {
            return $this->handleBookCatalogueException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }
}
