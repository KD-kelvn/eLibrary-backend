<?php

namespace Modules\BookCatalogue\Models;

use App\Models\BaseModelWithAudits;
use App\Support\QualifiedTable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubCategory extends BaseModelWithAudits
{
    protected $fillable = [
        'name',
        'description',
        'code',
    ];

    public function getTable(): string
    {
        return QualifiedTable::name('book_catalog', 'sub_categories');
    }

    public function bookHasSubCategories(): HasMany
    {
        return $this->hasMany(BookHasSubCategory::class, 'sub_category_id', 'id');
    }

    public function bookDetails(): BelongsToMany
    {
        return $this->belongsToMany(
            BookDetail::class,
            BookHasSubCategory::class,
            'sub_category_id',
            'book_detail_id',
        )->withPivot('id');
    }
}
