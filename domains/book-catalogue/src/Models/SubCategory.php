<?php

namespace Modules\BookCatalogue\Models;

use App\Models\BaseModelWithAudits;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubCategory extends BaseModelWithAudits
{
    protected $table = 'book_catalog.sub_categories';

    protected $fillable = [
        'name',
        'description',
        'code',
    ];

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
