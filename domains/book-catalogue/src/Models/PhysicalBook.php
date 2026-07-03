<?php

namespace Modules\BookCatalogue\Models;

use App\Models\BaseModelWithAudits;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhysicalBook extends BaseModelWithAudits
{
    protected $table = 'book_catalog.physical_books';

    protected $fillable = [
        'book_detail_id',
        'shelf_id',
        'code_no',
        'copies',
    ];

    protected function casts(): array
    {
        return [
            'copies' => 'integer',
        ];
    }

    public function bookDetail(): BelongsTo
    {
        return $this->belongsTo(BookDetail::class, 'book_detail_id', 'id');
    }

    public function shelf(): BelongsTo
    {
        return $this->belongsTo(Shelf::class, 'shelf_id', 'id');
    }
}
