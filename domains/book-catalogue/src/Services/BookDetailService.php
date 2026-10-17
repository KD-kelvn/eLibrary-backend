<?php

namespace Modules\BookCatalogue\Services;

use App\Repositories\FilesRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\BookCatalogue\Enums\BookTypeEnum;
use Modules\BookCatalogue\Enums\PhysicalBookCopyStatusEnum;
use Modules\BookCatalogue\Exceptions\BookCatalogueException;
use Modules\BookCatalogue\Models\BookDetail;
use Modules\BookCatalogue\Models\PhysicalBook;
use Modules\BookCatalogue\Repositories\BookDetailRepository;

class BookDetailService
{
    public function __construct(
        protected BookDetailRepository $repository,
        protected FilesRepository $files,
        protected EbookMetadataService $ebookMetadata,
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        return $this->repository->summary();
    }

    /**
     * @return Collection<int, BookDetail>
     */
    public function related(int $id, int $limit = 6)
    {
        $bookDetail = $this->findOrFail($id);

        return $this->repository->related($bookDetail, $limit);
    }

    public function downloadPath(int $id): array
    {
        $bookDetail = $this->show($id);
        $digital = $bookDetail->digitalBook;

        if (! $digital) {
            throw BookCatalogueException::forbidden('Only digital books can be downloaded.');
        }

        if (! $digital->is_downloadable) {
            throw BookCatalogueException::forbidden('Downloads are not allowed for this title.');
        }

        if (! $this->files->existsOnDisk($digital->file_path, 'ebooks')) {
            throw BookCatalogueException::notFound('Ebook file');
        }

        return [
            'path' => $digital->file_path,
            'name' => $digital->file_name ?: basename($digital->file_path),
            'type' => $digital->file_type ?: 'application/octet-stream',
        ];
    }

    public function show(int $id): BookDetail
    {
        $bookDetail = $this->repository->findByIdWithRelations($id);

        if (! $bookDetail) {
            throw BookCatalogueException::notFound('Book detail');
        }

        return $bookDetail;
    }

    public function store(array $data, ?UploadedFile $file = null, ?UploadedFile $cover = null): BookDetail
    {
        return DB::transaction(function () use ($data, $file, $cover) {
            $data = $this->applyDigitalUpload($data, $file, requireFile: true);
            $data = $this->applyCoverUpload($data, $cover);

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
                'file_cover',
            ])->all());

            $this->syncTypeRecord($bookDetail, $data);
            $this->syncPivots($bookDetail, $data);

            return $this->repository->findByIdWithRelations($bookDetail->id)
                ?? $bookDetail;
        });
    }

    public function update(int $id, array $data, ?UploadedFile $file = null, ?UploadedFile $cover = null): BookDetail
    {
        return DB::transaction(function () use ($id, $data, $file, $cover) {
            $bookDetail = $this->findOrFail($id);
            $data = $this->applyDigitalUpload($data, $file, requireFile: false, existing: $bookDetail);
            $data = $this->applyCoverUpload($data, $cover, $bookDetail);

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
                'file_cover',
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
            $digital = $bookDetail->digitalBook;
            if ($digital?->file_path) {
                $disk = $this->files->existsOnDisk($digital->file_path, 'ebooks') ? 'ebooks' : 'public';
                $this->files->delete($digital->file_path, $disk);
            }
            $this->deleteCoverFile($bookDetail->file_cover ?: $digital?->file_cover);

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
     * @return array<string, mixed>
     */
    protected function applyCoverUpload(
        array $data,
        ?UploadedFile $cover,
        ?BookDetail $existing = null,
    ): array {
        if (! $cover) {
            if ($existing && ! array_key_exists('file_cover', $data)) {
                $data['file_cover'] = $existing->file_cover;
            }

            return $data;
        }

        $storedCover = $this->files->saveFromUpload($cover, 'book-covers');
        $this->deleteCoverFile($existing?->file_cover);
        $data['file_cover'] = $storedCover['file_path'];

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function applyDigitalUpload(
        array $data,
        ?UploadedFile $file,
        bool $requireFile,
        ?BookDetail $existing = null,
    ): array {
        $type = $data['type_code'] ?? $existing?->type_code;
        $isDigital = $type === BookTypeEnum::Digital
            || $type === BookTypeEnum::Digital->value
            || (is_string($type) && strtoupper($type) === 'DIGITAL');

        if (! $isDigital) {
            return $data;
        }

        $digital = $data['digital_book'] ?? [];
        $current = $existing?->digitalBook;

        if ($file) {
            $meta = $this->ebookMetadata->inspectUpload($file);
            $stored = $this->files->saveFromUpload($file, 'ebooks', disk: 'ebooks');

            if ($current?->file_path) {
                $disk = $this->files->existsOnDisk($current->file_path, 'ebooks') ? 'ebooks' : 'public';
                $this->files->delete($current->file_path, $disk);
            }

            $digital = array_merge($digital, [
                'file_path' => $stored['file_path'],
                'file_name' => $file->getClientOriginalName() ?: $stored['file_name'],
                'file_type' => $stored['file_type'],
                'file_size' => (int) $stored['file_size'],
                'format' => $meta['format']->value,
                'checksum' => hash_file('sha256', $file->getRealPath() ?: $file->getPathname()) ?: null,
            ]);

            $data['pages'] = $meta['pages'];
        } elseif ($requireFile) {
            throw BookCatalogueException::unprocessable('An ebook file (PDF or EPUB) is required.');
        } elseif ($current) {
            // Keep existing file meta; allow toggling downloadable/active only.
            $digital = array_merge([
                'file_path' => $current->file_path,
                'file_name' => $current->file_name,
                'file_type' => $current->file_type,
                'file_size' => $current->file_size,
                'format' => $current->format?->value ?? $current->format,
                'checksum' => $current->checksum,
                'is_downloadable' => $current->is_downloadable,
                'is_active' => $current->is_active,
            ], $digital);
        }

        $data['digital_book'] = $digital;

        return $data;
    }

    protected function deleteCoverFile(?string $fileCover): void
    {
        $path = $this->coverStoragePath($fileCover);
        if ($path) {
            $this->files->delete($path);
        }
    }

    protected function syncPhysicalCopies(PhysicalBook $physicalBook, int $targetCount): void
    {
        $copies = $physicalBook->copyRecords()->lockForUpdate()->get();
        $currentCount = $copies->count();

        if ($targetCount > $currentCount) {
            $nextNumber = (int) $physicalBook->copyRecords()->withTrashed()->count() + 1;
            $prefix = mb_substr((string) $physicalBook->code_no, 0, 220);

            for ($number = $nextNumber; $number < $nextNumber + ($targetCount - $currentCount); $number++) {
                $physicalBook->copyRecords()->create([
                    'accession_number' => sprintf('%s-%d-%08d', $prefix, $physicalBook->id, $number),
                    'status' => PhysicalBookCopyStatusEnum::Available,
                ]);
            }

            return;
        }

        if ($targetCount < $currentCount) {
            $availableCopies = $copies
                ->where('status', PhysicalBookCopyStatusEnum::Available)
                ->sortByDesc('id');
            $removeCount = $currentCount - $targetCount;

            if ($availableCopies->count() < $removeCount) {
                throw BookCatalogueException::unprocessable(
                    'Copy count cannot be lower than the number of copies currently on loan, lost, or damaged.',
                );
            }

            $availableCopies->take($removeCount)->each->delete();
        }
    }

    protected function coverStoragePath(?string $fileCover): ?string
    {
        if (! $fileCover) {
            return null;
        }

        if (str_contains($fileCover, '/storage/')) {
            return ltrim(Str::after($fileCover, '/storage/'), '/');
        }

        if (preg_match('#^https?://#i', $fileCover)) {
            return null;
        }

        return ltrim($fileCover, '/');
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

            $physicalBook = $bookDetail->physicalBooks()->lockForUpdate()->first();

            if ($physicalBook) {
                $this->repository->updatePhysicalBook($physicalBook, $attributes);
            } else {
                $physicalBook = $this->repository->createPhysicalBook($bookDetail, $attributes);
            }

            $this->syncPhysicalCopies($physicalBook->refresh(), (int) ($attributes['copies'] ?? $physicalBook->copies));
        }

        if ($typeCode === BookTypeEnum::Digital && array_key_exists('digital_book', $data)) {
            $attributes = collect($data['digital_book'] ?? [])->only([
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
