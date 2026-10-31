<?php

namespace Modules\BookCatalogue\Models;

use App\Models\BaseModelWithAudits;
use App\Support\QualifiedTable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\BookCatalogue\Enums\EbookFormatEnum;

class DigitalBook extends BaseModelWithAudits
{
    protected $fillable = [
        'book_detail_id',
        'file_cover',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'format',
        'is_downloadable',
        'is_active',
        'checksum',
    ];

    public function getTable(): string
    {
        return QualifiedTable::name('book_catalog', 'digital_books');
    }

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'format' => EbookFormatEnum::class,
            'is_downloadable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function bookDetail(): BelongsTo
    {
        return $this->belongsTo(BookDetail::class, 'book_detail_id', 'id');
    }
}
