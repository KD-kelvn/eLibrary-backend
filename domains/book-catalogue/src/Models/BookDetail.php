<?php

namespace Modules\BookCatalogue\Models;

use App\Models\BaseModelWithAudits;
use App\Support\QualifiedTable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\BookCatalogue\Enums\BookTypeEnum;
use Modules\BookReading\Models\ReadingHistory;

class BookDetail extends BaseModelWithAudits
{
    protected $fillable = [
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
    ];

    protected function casts(): array
    {
        return [
            'type_code' => BookTypeEnum::class,
            'pub_year' => 'integer',
            'pages' => 'integer',
        ];
    }

    public function getTable(): string
    {
        return QualifiedTable::name('book_catalog', 'book_details');
    }

    public function physicalBooks(): HasMany
    {
        return $this->hasMany(PhysicalBook::class, 'book_detail_id', 'id');
    }

    public function copyRecords(): HasManyThrough
    {
        return $this->hasManyThrough(
            PhysicalBookCopy::class,
            PhysicalBook::class,
            'book_detail_id',
            'physical_book_id',
            'id',
            'id',
        );
    }

    public function digitalBook(): HasOne
    {
        return $this->hasOne(DigitalBook::class, 'book_detail_id', 'id');
    }

    public function bookHasCategories(): HasMany
    {
        return $this->hasMany(BookHasCategory::class, 'book_detail_id', 'id');
    }

    public function bookHasSubCategories(): HasMany
    {
        return $this->hasMany(BookHasSubCategory::class, 'book_detail_id', 'id');
    }

    public function bookHasTags(): HasMany
    {
        return $this->hasMany(BookHasTag::class, 'book_detail_id', 'id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            Category::class,
            (new BookHasCategory)->getTable(),
            'book_detail_id',
            'category_id',
        )->withPivot('id');
    }

    public function subCategories(): BelongsToMany
    {
        return $this->belongsToMany(
            SubCategory::class,
            (new BookHasSubCategory)->getTable(),
            'book_detail_id',
            'sub_category_id',
        )->withPivot('id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            Tag::class,
            (new BookHasTag)->getTable(),
            'book_detail_id',
            'tag_id',
        )->withPivot('id');
    }

    public function isPhysical(): bool
    {
        return $this->type_code === BookTypeEnum::Physical;
    }

    public function readingHistories(): HasMany
    {
        return $this->hasMany(ReadingHistory::class, 'book_detail_id');
    }

    public function isDigital(): bool
    {
        return $this->type_code === BookTypeEnum::Digital;
    }
}
