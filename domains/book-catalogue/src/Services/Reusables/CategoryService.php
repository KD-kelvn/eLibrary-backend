<?php

namespace Modules\BookCatalogue\Services\Reusables;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\BookCatalogue\Exceptions\BookCatalogueException;
use Modules\BookCatalogue\Models\Category;
use Modules\BookCatalogue\Repositories\Reusables\CategoryRepository;

class CategoryService
{
    public function __construct(protected CategoryRepository $repository) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function show(int $id): Category
    {
        $category = $this->repository->findByIdWithRelations($id);

        if (! $category) {
            throw BookCatalogueException::notFound('Category');
        }

        return $category;
    }

    public function store(array $data): Category
    {
        return $this->repository->create(collect($data)->only([
            'name',
            'description',
            'code',
        ])->all());
    }

    public function update(int $id, array $data): Category
    {
        $category = $this->findOrFail($id);

        return $this->repository->update($category, collect($data)->only([
            'name',
            'description',
            'code',
        ])->all());
    }

    public function destroy(int $id): void
    {
        $category = $this->findOrFail($id);

        if ($this->repository->countBooks($category) > 0) {
            throw BookCatalogueException::forbidden(
                'Cannot delete a category that is assigned to books.',
            );
        }

        $this->repository->delete($category);
    }

    protected function findOrFail(int $id): Category
    {
        $category = $this->repository->findById($id);

        if (! $category) {
            throw BookCatalogueException::notFound('Category');
        }

        return $category;
    }
}
