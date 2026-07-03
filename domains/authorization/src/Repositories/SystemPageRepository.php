<?php

namespace Modules\Authorization\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Authorization\Models\SystemPage;

class SystemPageRepository
{
    public function __construct(protected SystemPage $model) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->when(
                filled($filters['search'] ?? null),
                fn ($query) => $query->where(function ($builder) use ($filters) {
                    $search = $filters['search'];
                    $builder->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                }),
            )
            ->when(
                isset($filters['is_public']),
                fn ($query) => $query->where('is_public', (bool) $filters['is_public']),
            )
            ->latest('id')
            ->paginate($perPage);
    }

    public function findById(int $id): ?SystemPage
    {
        return $this->model->newQuery()->find($id);
    }

    public function findByIdWithRelations(int $id): ?SystemPage
    {
        return $this->model->newQuery()
            ->with([
                'roles',
                'systemPageRoles.role',
            ])
            ->withCount('systemPageRoles')
            ->find($id);
    }

    public function create(array $attributes): SystemPage
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function update(SystemPage $page, array $attributes): SystemPage
    {
        $page->update($attributes);

        return $page->refresh();
    }

    public function delete(SystemPage $page): bool
    {
        return (bool) $page->delete();
    }
}
