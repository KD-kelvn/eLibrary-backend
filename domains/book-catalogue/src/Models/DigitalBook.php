<?php

namespace Modules\BookCatalogue\Models;

use App\Models\BaseModelWithAudits;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\BookCatalogue\Enums\EbookFormatEnum;

class DigitalBook extends BaseModelWithAudits
{
    protected $table = 'book_catalog.digital_books';

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
