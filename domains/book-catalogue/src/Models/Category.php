<?php

namespace Modules\BookCatalogue\Models;

use App\Models\BaseModelWithAudits;
use App\Support\QualifiedTable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends BaseModelWithAudits
{
    protected $fillable = [
        'name',
        'description',
        'code',
    ];

    public function getTable(): string
    {
        return QualifiedTable::name('book_catalog', 'categories');
    }

    public function bookHasCategories(): HasMany
    {
        return $this->hasMany(BookHasCategory::class, 'category_id', 'id');
    }

    public function bookDetails(): BelongsToMany
    {
        return $this->belongsToMany(
            BookDetail::class,
            BookHasCategory::class,
            'category_id',
            'book_detail_id',
        )->withPivot('id');
    }
}
