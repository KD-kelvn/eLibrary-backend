<?php

namespace Modules\BookCatalogue\Models;

use App\Models\BaseModelWithAudits;
use App\Support\QualifiedTable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Authentication\Models\User;

class BookFavourite extends BaseModelWithAudits
{
    protected $fillable = [
        'user_id',
        'book_detail_id',
    ];

    public function getTable(): string
    {
        return QualifiedTable::name('book_catalog', 'book_favourites');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function bookDetail(): BelongsTo
    {
        return $this->belongsTo(BookDetail::class, 'book_detail_id');
    }
}
