<?php

namespace Modules\BookCatalogue\Repositories\Reusables;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\BookCatalogue\Models\Shelf;

class ShelfRepository
{
    public function __construct(protected Shelf $model) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->withCount('physicalBooks')
            ->when(
                filled($filters['search'] ?? null),
                fn ($query) => $query->where(function ($builder) use ($filters) {
                    $search = $filters['search'];
                    $builder->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                }),
            )
            ->latest('id')
            ->paginate($perPage);
    }

    public function findById(int $id): ?Shelf
    {
        return $this->model->newQuery()->find($id);
    }

    public function findByIdWithRelations(int $id): ?Shelf
    {
        return $this->model->newQuery()
            ->with(['physicalBooks.bookDetail'])
            ->withCount('physicalBooks')
            ->find($id);
    }

    public function create(array $attributes): Shelf
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function update(Shelf $shelf, array $attributes): Shelf
    {
        $shelf->update($attributes);

        return $shelf->refresh();
    }

    public function delete(Shelf $shelf): bool
    {
        return (bool) $shelf->delete();
    }

    public function countPhysicalBooks(Shelf $shelf): int
    {
        return $shelf->physicalBooks()->count();
    }
}
