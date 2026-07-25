<?php

namespace Modules\BookCatalogue\Http\Controllers\Reusables;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\BookCatalogue\Exceptions\BookCatalogueException;
use Modules\BookCatalogue\Http\Requests\Reusables\IndexSubCategoryRequest;
use Modules\BookCatalogue\Http\Requests\Reusables\StoreSubCategoryRequest;
use Modules\BookCatalogue\Http\Requests\Reusables\UpdateSubCategoryRequest;
use Modules\BookCatalogue\Http\Resources\SubCategoryResource;
use Modules\BookCatalogue\Models\SubCategory;
use Modules\BookCatalogue\Services\Reusables\SubCategoryService;
use Modules\BookCatalogue\Traits\HandlesApiResponses;

class SubCategoryController extends Controller
{
    use HandlesApiResponses;

    public function __construct(private readonly SubCategoryService $subCategoryService) {}

    public function index(IndexSubCategoryRequest $request): JsonResponse
    {
        try {
            $paginator = $this->subCategoryService->list(
                $request->validated(),
                (int) $request->input('per_page', 15),
            );

            return $this->paginatedResponse($paginator, SubCategoryResource::class, 'Sub categories retrieved.');
        } catch (BookCatalogueException $exception) {
            return $this->handleBookCatalogueException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function show(string $sub_category): JsonResponse
    {
        try {
            $this->authorize('view', SubCategory::query()->findOrFail($sub_category));

            return $this->resourceResponse(
                SubCategoryResource::make($this->subCategoryService->show((int) $sub_category)),
                'Sub category retrieved.',
            );
        } catch (BookCatalogueException $exception) {
            return $this->handleBookCatalogueException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function store(StoreSubCategoryRequest $request): JsonResponse
    {
        try {
            $subCategory = $this->subCategoryService->store($request->validated());

            return $this->resourceResponse(
                SubCategoryResource::make($subCategory),
                'Sub category created.',
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

    public function update(UpdateSubCategoryRequest $request, string $sub_category): JsonResponse
    {
        try {
            $updated = $this->subCategoryService->update((int) $sub_category, $request->validated());

            return $this->resourceResponse(
                SubCategoryResource::make($updated),
                'Sub category updated.',
            );
        } catch (BookCatalogueException $exception) {
            return $this->handleBookCatalogueException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function destroy(string $sub_category): JsonResponse
    {
        try {
            $model = SubCategory::query()->findOrFail($sub_category);
            $this->authorize('delete', $model);

            $this->subCategoryService->destroy((int) $sub_category);

            return $this->successResponse(null, 'Sub category deleted.');
        } catch (BookCatalogueException $exception) {
            return $this->handleBookCatalogueException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }
}
