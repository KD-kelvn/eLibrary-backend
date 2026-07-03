<?php

namespace Modules\BookCatalogue\Models;

use App\Models\BaseModelWithAudits;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookHasCategory extends BaseModelWithAudits
{
    protected $table = 'book_catalog.book_has_categories';

    protected $fillable = [
        'book_detail_id',
        'category_id',
    ];

    public function bookDetail(): BelongsTo
    {
        return $this->belongsTo(BookDetail::class, 'book_detail_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
