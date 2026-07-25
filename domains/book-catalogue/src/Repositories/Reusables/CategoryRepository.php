<?php

namespace Modules\BookCatalogue\Repositories\Reusables;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\BookCatalogue\Models\Category;

class CategoryRepository
{
    public function __construct(protected Category $model) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->withCount('bookHasCategories')
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

    public function findById(int $id): ?Category
    {
        return $this->model->newQuery()->find($id);
    }

    public function findByIdWithRelations(int $id): ?Category
    {
        return $this->model->newQuery()
            ->with(['bookDetails'])
            ->withCount('bookHasCategories')
            ->find($id);
    }

    public function create(array $attributes): Category
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function update(Category $category, array $attributes): Category
    {
        $category->update($attributes);

        return $category->refresh();
    }

    public function delete(Category $category): bool
    {
        return (bool) $category->delete();
    }

    public function countBooks(Category $category): int
    {
        return $category->bookHasCategories()->count();
    }
}
