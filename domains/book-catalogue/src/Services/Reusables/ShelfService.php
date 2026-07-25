<?php

namespace Modules\BookCatalogue\Services\Reusables;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\BookCatalogue\Exceptions\BookCatalogueException;
use Modules\BookCatalogue\Models\Shelf;
use Modules\BookCatalogue\Repositories\Reusables\ShelfRepository;

class ShelfService
{
    public function __construct(protected ShelfRepository $repository) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function show(int $id): Shelf
    {
        $shelf = $this->repository->findByIdWithRelations($id);

        if (! $shelf) {
            throw BookCatalogueException::notFound('Shelf');
        }

        return $shelf;
    }

    public function store(array $data): Shelf
    {
        return $this->repository->create(collect($data)->only([
            'name',
            'description',
            'code',
            'location',
            'number',
            'rack',
        ])->all());
    }

    public function update(int $id, array $data): Shelf
    {
        $shelf = $this->findOrFail($id);

        return $this->repository->update($shelf, collect($data)->only([
            'name',
            'description',
            'code',
            'location',
            'number',
            'rack',
        ])->all());
    }

    public function destroy(int $id): void
    {
        $shelf = $this->findOrFail($id);

        if ($this->repository->countPhysicalBooks($shelf) > 0) {
            throw BookCatalogueException::forbidden(
                'Cannot delete a shelf that has physical books.',
            );
        }

        $this->repository->delete($shelf);
    }

    protected function findOrFail(int $id): Shelf
    {
        $shelf = $this->repository->findById($id);

        if (! $shelf) {
            throw BookCatalogueException::notFound('Shelf');
        }

        return $shelf;
    }
}
