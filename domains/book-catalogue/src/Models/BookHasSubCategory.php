<?php

namespace Modules\BookCatalogue\Models;

use App\Models\BaseModelWithAudits;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookHasSubCategory extends BaseModelWithAudits
{
    protected $table = 'book_catalog.book_has_sub_categories';

    protected $fillable = [
        'book_detail_id',
        'sub_category_id',
    ];

    public function bookDetail(): BelongsTo
    {
        return $this->belongsTo(BookDetail::class, 'book_detail_id');
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class, 'sub_category_id');
    }
}
