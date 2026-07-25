<?php

namespace Modules\BookCatalogue\Repositories\Reusables;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\BookCatalogue\Models\SubCategory;

class SubCategoryRepository
{
    public function __construct(protected SubCategory $model) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->withCount('bookHasSubCategories')
            ->when(
                filled($filters['search'] ?? null),
                fn ($query) => $query->where(function ($builder) use ($filters) {
                    $search = $filters['search'];
                    $builder->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                }),
            )
            ->latest('id')
            ->paginate($perPage);
    }

    public function findById(int $id): ?SubCategory
    {
        return $this->model->newQuery()->find($id);
    }

    public function findByIdWithRelations(int $id): ?SubCategory
    {
        return $this->model->newQuery()
            ->with(['bookDetails'])
            ->withCount('bookHasSubCategories')
            ->find($id);
    }

    public function create(array $attributes): SubCategory
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function update(SubCategory $subCategory, array $attributes): SubCategory
    {
        $subCategory->update($attributes);

        return $subCategory->refresh();
    }

    public function delete(SubCategory $subCategory): bool
    {
        return (bool) $subCategory->delete();
    }

    public function countBooks(SubCategory $subCategory): int
    {
        return $subCategory->bookHasSubCategories()->count();
    }
}
