<?php

namespace Modules\BookCatalogue\Models;

use App\Models\BaseModelWithAudits;
use App\Support\QualifiedTable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookHasSubCategory extends BaseModelWithAudits
{
    protected $fillable = [
        'book_detail_id',
        'sub_category_id',
    ];

    public function getTable(): string
    {
        return QualifiedTable::name('book_catalog', 'book_has_sub_categories');
    }

    public function bookDetail(): BelongsTo
    {
        return $this->belongsTo(BookDetail::class, 'book_detail_id');
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class, 'sub_category_id');
    }
}
