<?php

namespace Modules\BookCatalogue\Services\Reusables;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\BookCatalogue\Exceptions\BookCatalogueException;
use Modules\BookCatalogue\Models\Tag;
use Modules\BookCatalogue\Repositories\Reusables\TagRepository;

class TagService
{
    public function __construct(protected TagRepository $repository) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function show(int $id): Tag
    {
        $tag = $this->repository->findByIdWithRelations($id);

        if (! $tag) {
            throw BookCatalogueException::notFound('Tag');
        }

        return $tag;
    }

    public function store(array $data): Tag
    {
        return $this->repository->create(collect($data)->only([
            'name',
            'description',
            'code',
        ])->all());
    }

    public function update(int $id, array $data): Tag
    {
        $tag = $this->findOrFail($id);

        return $this->repository->update($tag, collect($data)->only([
            'name',
            'description',
            'code',
        ])->all());
    }

    public function destroy(int $id): void
    {
        $tag = $this->findOrFail($id);

        if ($this->repository->countBooks($tag) > 0) {
            throw BookCatalogueException::forbidden(
                'Cannot delete a tag that is assigned to books.',
            );
        }

        $this->repository->delete($tag);
    }

    protected function findOrFail(int $id): Tag
    {
        $tag = $this->repository->findById($id);

        if (! $tag) {
            throw BookCatalogueException::notFound('Tag');
        }

        return $tag;
    }
}
