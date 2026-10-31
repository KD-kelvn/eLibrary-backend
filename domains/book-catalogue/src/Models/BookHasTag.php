<?php

namespace Modules\BookCatalogue\Models;

use App\Models\BaseModelWithAudits;
use App\Support\QualifiedTable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookHasTag extends BaseModelWithAudits
{
    protected $fillable = [
        'book_detail_id',
        'tag_id',
    ];

    public function getTable(): string
    {
        return QualifiedTable::name('book_catalog', 'book_has_tags');
    }

    public function bookDetail(): BelongsTo
    {
        return $this->belongsTo(BookDetail::class, 'book_detail_id');
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class, 'tag_id');
    }
}
