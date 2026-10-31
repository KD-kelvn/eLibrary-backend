<?php

namespace Modules\BookCatalogue\Models;

use App\Models\BaseModelWithAudits;
use App\Support\QualifiedTable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhysicalBook extends BaseModelWithAudits
{
    protected $fillable = [
        'book_detail_id',
        'shelf_id',
        'code_no',
        'copies',
    ];

    public function getTable(): string
    {
        return QualifiedTable::name('book_catalog', 'physical_books');
    }

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

    public function copyRecords(): HasMany
    {
        return $this->hasMany(PhysicalBookCopy::class, 'physical_book_id');
    }
}
