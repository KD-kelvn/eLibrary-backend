<?php

namespace Modules\BookCatalogue\Services\Reusables;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\BookCatalogue\Exceptions\BookCatalogueException;
use Modules\BookCatalogue\Models\SubCategory;
use Modules\BookCatalogue\Repositories\Reusables\SubCategoryRepository;

class SubCategoryService
{
    public function __construct(protected SubCategoryRepository $repository) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function show(int $id): SubCategory
    {
        $subCategory = $this->repository->findByIdWithRelations($id);

        if (! $subCategory) {
            throw BookCatalogueException::notFound('Sub category');
        }

        return $subCategory;
    }

    public function store(array $data): SubCategory
    {
        return $this->repository->create(collect($data)->only([
            'name',
            'description',
            'code',
        ])->all());
    }

    public function update(int $id, array $data): SubCategory
    {
        $subCategory = $this->findOrFail($id);

        return $this->repository->update($subCategory, collect($data)->only([
            'name',
            'description',
            'code',
        ])->all());
    }

    public function destroy(int $id): void
    {
        $subCategory = $this->findOrFail($id);

        if ($this->repository->countBooks($subCategory) > 0) {
            throw BookCatalogueException::forbidden(
                'Cannot delete a sub category that is assigned to books.',
            );
        }

        $this->repository->delete($subCategory);
    }

    protected function findOrFail(int $id): SubCategory
    {
        $subCategory = $this->repository->findById($id);

        if (! $subCategory) {
            throw BookCatalogueException::notFound('Sub category');
        }

        return $subCategory;
    }
}
