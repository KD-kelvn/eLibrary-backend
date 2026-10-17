<?php

namespace Modules\BookCatalogue\Models;

use App\Models\BaseModelWithAudits;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\BookCatalogue\Enums\PhysicalBookCopyStatusEnum;

class PhysicalBookCopy extends BaseModelWithAudits
{
    protected $table = 'book_catalog.physical_book_copies';

    protected $fillable = [
        'physical_book_id',
        'accession_number',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => PhysicalBookCopyStatusEnum::class,
        ];
    }

    public function physicalBook(): BelongsTo
    {
        return $this->belongsTo(PhysicalBook::class, 'physical_book_id');
    }
}
