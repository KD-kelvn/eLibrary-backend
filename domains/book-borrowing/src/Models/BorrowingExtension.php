<?php

namespace Modules\BookBorrowing\Models;

use App\Models\BaseModelWithAudits;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BorrowingExtension extends BaseModelWithAudits
{
    protected $table = 'book_borrowing.borrowing_extensions';

    protected $fillable = [
        'borrowing_request_id',
        'book_type',
        'book_type_id',
        'start_date',
        'end_date',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }

    public function borrowingRequest(): BelongsTo
    {
        return $this->belongsTo(BorrowingRequest::class, 'borrowing_request_id');
    }

    public function bookType(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'book_type', 'book_type_id');
    }
}
