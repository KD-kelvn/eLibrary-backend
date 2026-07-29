<?php

namespace Modules\BookCatalogue\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\BookCatalogue\Enums\BookTypeEnum;
use Modules\BookCatalogue\Exceptions\BookCatalogueException;
use Modules\BookCatalogue\Models\BookDetail;
use Modules\BookCatalogue\Repositories\BookDetailRepository;

class BookDetailService
{
    public function __construct(protected BookDetailRepository $repository) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function show(int $id): BookDetail
    {
        $bookDetail = $this->repository->findByIdWithRelations($id);

        if (! $bookDetail) {
            throw BookCatalogueException::notFound('Book detail');
        }

        return $bookDetail;
    }

    public function store(array $data): BookDetail
    {
        return DB::transaction(function () use ($data) {
            $bookDetail = $this->repository->create(collect($data)->only([
                'type_code',
                'title',
                'description',
                'authors',
                'isbn',
                'publisher',
                'pub_year',
                'edition',
                'language',
                'pages',
            ])->all());

            $this->syncTypeRecord($bookDetail, $data);
            $this->syncPivots($bookDetail, $data);

            return $this->repository->findByIdWithRelations($bookDetail->id)
                ?? $bookDetail;
        });
    }

    public function update(int $id, array $data): BookDetail
    {
        return DB::transaction(function () use ($id, $data) {
            $bookDetail = $this->findOrFail($id);

            $this->repository->update($bookDetail, collect($data)->only([
                'type_code',
                'title',
                'description',
                'authors',
                'isbn',
                'publisher',
                'pub_year',
                'edition',
                'language',
                'pages',
            ])->all());

            $bookDetail->refresh();

            $this->syncTypeRecord($bookDetail, $data);
            $this->syncPivots($bookDetail, $data);

            return $this->repository->findByIdWithRelations($bookDetail->id)
                ?? $bookDetail;
        });
    }

    public function destroy(int $id): void
    {
        $bookDetail = $this->findOrFail($id);

        if ($this->repository->countReadingHistories($bookDetail) > 0) {
            throw BookCatalogueException::forbidden(
                'Cannot delete a book that has reading histories.',
            );
        }

        DB::transaction(function () use ($bookDetail) {
            $this->repository->softDeletePhysicalBooks($bookDetail);
            $this->repository->softDeleteDigitalBook($bookDetail);
            $this->repository->delete($bookDetail);
        });
    }

    protected function findOrFail(int $id): BookDetail
    {
        $bookDetail = $this->repository->findById($id);

        if (! $bookDetail) {
            throw BookCatalogueException::notFound('Book detail');
        }

        return $bookDetail;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function syncTypeRecord(BookDetail $bookDetail, array $data): void
    {
        $typeCode = $bookDetail->type_code;

        if ($typeCode === BookTypeEnum::Physical && array_key_exists('physical_book', $data)) {
            $attributes = collect($data['physical_book'] ?? [])->only([
                'shelf_id',
                'code_no',
                'copies',
            ])->all();

            $physicalBook = $bookDetail->physicalBooks()->first();

            if ($physicalBook) {
                $this->repository->updatePhysicalBook($physicalBook, $attributes);
            } else {
                $this->repository->createPhysicalBook($bookDetail, $attributes);
            }
        }

        if ($typeCode === BookTypeEnum::Digital && array_key_exists('digital_book', $data)) {
            $attributes = collect($data['digital_book'] ?? [])->only([
                'file_cover',
                'file_path',
                'file_name',
                'file_type',
                'file_size',
                'format',
                'is_downloadable',
                'is_active',
                'checksum',
            ])->all();

            $digitalBook = $bookDetail->digitalBook;

            if ($digitalBook) {
                $this->repository->updateDigitalBook($digitalBook, $attributes);
            } else {
                $this->repository->createDigitalBook($bookDetail, $attributes);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function syncPivots(BookDetail $bookDetail, array $data): void
    {
        if (array_key_exists('category_ids', $data)) {
            $this->repository->syncCategories($bookDetail, $data['category_ids'] ?? []);
        }

        if (array_key_exists('sub_category_ids', $data)) {
            $this->repository->syncSubCategories($bookDetail, $data['sub_category_ids'] ?? []);
        }

        if (array_key_exists('tag_ids', $data)) {
            $this->repository->syncTags($bookDetail, $data['tag_ids'] ?? []);
        }
    }
}
