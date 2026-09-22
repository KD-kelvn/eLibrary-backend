<?php

namespace Modules\BookCatalogue\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\BookCatalogue\Models\BookDetail;
use Modules\BookCatalogue\Models\DigitalBook;
use Modules\BookCatalogue\Models\PhysicalBook;

class BookDetailRepository
{
    public function __construct(
        protected BookDetail $model
    ) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this
            ->model
            ->newQuery()
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
                fn ($query) => $query->where('title', 'like', "%{$filters['search']}%"),
            )
            ->when(
                filled($filters['type_code'] ?? null),
                fn ($query) => $query->where('type_code', $filters['type_code']),
            )
            ->when(
                filled($filters['category_id'] ?? null),
                fn ($query) => $query->whereHas(
                    'categories',
                    fn ($builder) => $builder->whereKey($filters['category_id']),
                ),
            )
            ->when(
                filled($filters['sub_category_id'] ?? null),
                fn ($query) => $query->whereHas(
                    'subCategories',
                    fn ($builder) => $builder->whereKey($filters['sub_category_id']),
                ),
            )
            ->when(
                filled($filters['tag_id'] ?? null),
                fn ($query) => $query->whereHas(
                    'tags',
                    fn ($builder) => $builder->whereKey($filters['tag_id']),
                ),
            )
            ->when(
                ($filters['status'] ?? null) === 'active',
                fn ($query) => $query->where(function ($builder) {
                    $builder
                        ->where('type_code', 'PHYSICAL')
                        ->orWhereHas(
                            'digitalBook',
                            fn ($digital) => $digital->where('is_active', true),
                        );
                }),
            )
            ->when(
                ($filters['status'] ?? null) === 'inactive',
                fn ($query) => $query
                    ->where('type_code', 'DIGITAL')
                    ->whereHas(
                        'digitalBook',
                        fn ($digital) => $digital->where('is_active', false),
                    ),
            )
            ->when(
                filled($filters['date_from'] ?? null),
                fn ($query) => $query->whereDate('created_at', '>=', $filters['date_from']),
            )
            ->when(
                filled($filters['date_to'] ?? null),
                fn ($query) => $query->whereDate('created_at', '<=', $filters['date_to']),
            )
            ->when(
                filled($filters['pub_year_from'] ?? null),
                fn ($query) => $query->where('pub_year', '>=', (int) $filters['pub_year_from']),
            )
            ->when(
                filled($filters['pub_year_to'] ?? null),
                fn ($query) => $query->where('pub_year', '<=', (int) $filters['pub_year_to']),
            )
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * Catalogue KPI counts for the books listing header.
     *
     * @return array{
     *     total: int,
     *     byType: list<array{value: string, label: string, count: int}>,
     *     byStatus: list<array{value: string, label: string, count: int}>
     * }
     */
    public function summary(): array
    {
        $total = $this->model->newQuery()->count();

        $typeRows = $this
            ->model
            ->newQuery()
            ->selectRaw('type_code, COUNT(*) as aggregate')
            ->groupBy('type_code')
            ->get()
            ->mapWithKeys(function ($row) {
                $key = $row->type_code;
                if (is_object($key) && property_exists($key, 'value')) {
                    $key = $key->value;
                }

                return [(string) $key => (int) $row->aggregate];
            });

        $activeDigital = DigitalBook::query()->where('is_active', true)->count();
        $inactiveDigital = DigitalBook::query()->where('is_active', false)->count();
        $physicalCount = (int) ($typeRows['PHYSICAL'] ?? 0);

        return [
            'total' => $total,
            'byType' => [
                [
                    'value' => 'PHYSICAL',
                    'label' => 'Physical',
                    'count' => $physicalCount,
                ],
                [
                    'value' => 'DIGITAL',
                    'label' => 'Digital',
                    'count' => (int) ($typeRows['DIGITAL'] ?? 0),
                ],
            ],
            'byStatus' => [
                [
                    'value' => 'active',
                    'label' => 'Active',
                    'count' => $physicalCount + $activeDigital,
                ],
                [
                    'value' => 'inactive',
                    'label' => 'Inactive',
                    'count' => $inactiveDigital,
                ],
            ],
        ];
    }

    /**
     * Related titles sharing type and at least one category.
     *
     * @return Collection<int, BookDetail>
     */
    public function related(BookDetail $book, int $limit = 6): Collection
    {
        $categoryIds = $book->categories()->allRelatedIds();

        return $this
            ->model
            ->newQuery()
            ->with(['digitalBook', 'categories', 'tags'])
            ->whereKeyNot($book->id)
            ->where('type_code', $book->type_code)
            ->when(
                $categoryIds->isNotEmpty(),
                fn ($query) => $query->whereHas(
                    'categories',
                    fn ($builder) => $builder->whereKey($categoryIds->all()),
                ),
            )
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function findById(int $id): ?BookDetail
    {
        return $this->model->newQuery()->find($id);
    }

    public function findByIdWithRelations(int $id): ?BookDetail
    {
        return $this
            ->model
            ->newQuery()
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
