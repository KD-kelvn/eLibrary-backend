<?php

namespace Modules\BookCatalogue\Repositories\Reusables;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\BookCatalogue\Models\Tag;

class TagRepository
{
    public function __construct(protected Tag $model) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->withCount('bookHasTags')
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

    public function findById(int $id): ?Tag
    {
        return $this->model->newQuery()->find($id);
    }

    public function findByIdWithRelations(int $id): ?Tag
    {
        return $this->model->newQuery()
            ->with(['bookDetails'])
            ->withCount('bookHasTags')
            ->find($id);
    }

    public function create(array $attributes): Tag
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function update(Tag $tag, array $attributes): Tag
    {
        $tag->update($attributes);

        return $tag->refresh();
    }

    public function delete(Tag $tag): bool
    {
        return (bool) $tag->delete();
    }

    public function countBooks(Tag $tag): int
    {
        return $tag->bookHasTags()->count();
    }
}
