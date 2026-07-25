<?php

namespace Modules\BookCatalogue\Http\Controllers\Reusables;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\BookCatalogue\Exceptions\BookCatalogueException;
use Modules\BookCatalogue\Http\Requests\Reusables\IndexCategoryRequest;
use Modules\BookCatalogue\Http\Requests\Reusables\StoreCategoryRequest;
use Modules\BookCatalogue\Http\Requests\Reusables\UpdateCategoryRequest;
use Modules\BookCatalogue\Http\Resources\CategoryResource;
use Modules\BookCatalogue\Models\Category;
use Modules\BookCatalogue\Services\Reusables\CategoryService;
use Modules\BookCatalogue\Traits\HandlesApiResponses;

class CategoryController extends Controller
{
    use HandlesApiResponses;

    public function __construct(private readonly CategoryService $categoryService) {}

    public function index(IndexCategoryRequest $request): JsonResponse
    {
        try {
            $paginator = $this->categoryService->list(
                $request->validated(),
                (int) $request->input('per_page', 15),
            );

            return $this->paginatedResponse($paginator, CategoryResource::class, 'Categories retrieved.');
        } catch (BookCatalogueException $exception) {
            return $this->handleBookCatalogueException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function show(string $category): JsonResponse
    {
        try {
            $this->authorize('view', Category::query()->findOrFail($category));

            return $this->resourceResponse(
                CategoryResource::make($this->categoryService->show((int) $category)),
                'Category retrieved.',
            );
        } catch (BookCatalogueException $exception) {
            return $this->handleBookCatalogueException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        try {
            $category = $this->categoryService->store($request->validated());

            return $this->resourceResponse(
                CategoryResource::make($category),
                'Category created.',
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

    public function update(UpdateCategoryRequest $request, string $category): JsonResponse
    {
        try {
            $updated = $this->categoryService->update((int) $category, $request->validated());

            return $this->resourceResponse(
                CategoryResource::make($updated),
                'Category updated.',
            );
        } catch (BookCatalogueException $exception) {
            return $this->handleBookCatalogueException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function destroy(string $category): JsonResponse
    {
        try {
            $model = Category::query()->findOrFail($category);
            $this->authorize('delete', $model);

            $this->categoryService->destroy((int) $category);

            return $this->successResponse(null, 'Category deleted.');
        } catch (BookCatalogueException $exception) {
            return $this->handleBookCatalogueException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }
}
