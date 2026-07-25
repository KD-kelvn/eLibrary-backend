<?php

namespace Modules\BookCatalogue\Http\Controllers\Reusables;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\BookCatalogue\Exceptions\BookCatalogueException;
use Modules\BookCatalogue\Http\Requests\Reusables\IndexTagRequest;
use Modules\BookCatalogue\Http\Requests\Reusables\StoreTagRequest;
use Modules\BookCatalogue\Http\Requests\Reusables\UpdateTagRequest;
use Modules\BookCatalogue\Http\Resources\TagResource;
use Modules\BookCatalogue\Models\Tag;
use Modules\BookCatalogue\Services\Reusables\TagService;
use Modules\BookCatalogue\Traits\HandlesApiResponses;

class TagController extends Controller
{
    use HandlesApiResponses;

    public function __construct(private readonly TagService $tagService) {}

    public function index(IndexTagRequest $request): JsonResponse
    {
        try {
            $paginator = $this->tagService->list(
                $request->validated(),
                (int) $request->input('per_page', 15),
            );

            return $this->paginatedResponse($paginator, TagResource::class, 'Tags retrieved.');
        } catch (BookCatalogueException $exception) {
            return $this->handleBookCatalogueException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function show(string $tag): JsonResponse
    {
        try {
            $this->authorize('view', Tag::query()->findOrFail($tag));

            return $this->resourceResponse(
                TagResource::make($this->tagService->show((int) $tag)),
                'Tag retrieved.',
            );
        } catch (BookCatalogueException $exception) {
            return $this->handleBookCatalogueException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function store(StoreTagRequest $request): JsonResponse
    {
        try {
            $tag = $this->tagService->store($request->validated());

            return $this->resourceResponse(
                TagResource::make($tag),
                'Tag created.',
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

    public function update(UpdateTagRequest $request, string $tag): JsonResponse
    {
        try {
            $updated = $this->tagService->update((int) $tag, $request->validated());

            return $this->resourceResponse(
                TagResource::make($updated),
                'Tag updated.',
            );
        } catch (BookCatalogueException $exception) {
            return $this->handleBookCatalogueException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function destroy(string $tag): JsonResponse
    {
        try {
            $model = Tag::query()->findOrFail($tag);
            $this->authorize('delete', $model);

            $this->tagService->destroy((int) $tag);

            return $this->successResponse(null, 'Tag deleted.');
        } catch (BookCatalogueException $exception) {
            return $this->handleBookCatalogueException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }
}
