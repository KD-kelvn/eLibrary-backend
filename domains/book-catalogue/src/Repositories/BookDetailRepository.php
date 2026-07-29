<?php

namespace Modules\BookCatalogue\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\BookCatalogue\Models\BookDetail;
use Modules\BookCatalogue\Models\DigitalBook;
use Modules\BookCatalogue\Models\PhysicalBook;

class BookDetailRepository
{
    public function __construct(protected BookDetail $model) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with([
                'physicalBooks.shelf',
                'digitalBook',
                'categories',
                'subCategories',
                'tags',
            ])
            ->withCount(['physicalBooks', 'readingHistories'])
            ->when(
                filled($filters['search'] ?? null),
                fn ($query) => $query->where(function ($builder) use ($filters) {
                    $search = $filters['search'];
                    $builder->where('title', 'like', "%{$search}%")
                        ->orWhere('authors', 'like', "%{$search}%")
                        ->orWhere('isbn', 'like', "%{$search}%");
                }),
            )
            ->when(
                filled($filters['type_code'] ?? null),
                fn ($query) => $query->where('type_code', $filters['type_code']),
            )
            ->latest('id')
            ->paginate($perPage);
    }

    public function findById(int $id): ?BookDetail
    {
        return $this->model->newQuery()->find($id);
    }

    public function findByIdWithRelations(int $id): ?BookDetail
    {
        return $this->model->newQuery()
            ->with([
                'physicalBooks.shelf',
                'digitalBook',
                'categories',
                'subCategories',
                'tags',
            ])
            ->withCount(['physicalBooks', 'readingHistories'])
            ->find($id);
    }

    public function create(array $attributes): BookDetail
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function update(BookDetail $bookDetail, array $attributes): BookDetail
    {
        $bookDetail->update($attributes);

        return $bookDetail->refresh();
    }

    public function delete(BookDetail $bookDetail): bool
    {
        return (bool) $bookDetail->delete();
    }

    public function countReadingHistories(BookDetail $bookDetail): int
    {
        return $bookDetail->readingHistories()->count();
    }

    public function createPhysicalBook(BookDetail $bookDetail, array $attributes): PhysicalBook
    {
        return $bookDetail->physicalBooks()->create($attributes);
    }

    public function updatePhysicalBook(PhysicalBook $physicalBook, array $attributes): PhysicalBook
    {
        $physicalBook->update($attributes);

        return $physicalBook->refresh();
    }

    public function createDigitalBook(BookDetail $bookDetail, array $attributes): DigitalBook
    {
        return $bookDetail->digitalBook()->create($attributes);
    }

    public function updateDigitalBook(DigitalBook $digitalBook, array $attributes): DigitalBook
    {
        $digitalBook->update($attributes);

        return $digitalBook->refresh();
    }

    public function softDeletePhysicalBooks(BookDetail $bookDetail): void
    {
        $bookDetail->physicalBooks()->each(fn (PhysicalBook $book) => $book->delete());
    }

    public function softDeleteDigitalBook(BookDetail $bookDetail): void
    {
        $bookDetail->digitalBook?->delete();
    }

    /**
     * @param  list<int>  $categoryIds
     */
    public function syncCategories(BookDetail $bookDetail, array $categoryIds): void
    {
        $bookDetail->categories()->sync($categoryIds);
    }

    /**
     * @param  list<int>  $subCategoryIds
     */
    public function syncSubCategories(BookDetail $bookDetail, array $subCategoryIds): void
    {
        $bookDetail->subCategories()->sync($subCategoryIds);
    }

    /**
     * @param  list<int>  $tagIds
     */
    public function syncTags(BookDetail $bookDetail, array $tagIds): void
    {
        $bookDetail->tags()->sync($tagIds);
    }
}
