<?php

namespace Modules\BookBorrowing\Models;

use App\Models\BaseModelWithAudits;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoppedPenalty extends BaseModelWithAudits
{
    protected $table = 'book_borrowing.stopped_penalties';

    protected $fillable = [
        'penalty_batch_id',
        'borrowing_penalty_id',
        'stopped_date',
        'stopped_reason',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'stopped_date' => 'datetime',
        ];
    }

    public function penaltyBatch(): BelongsTo
    {
        return $this->belongsTo(PenaltyBatch::class, 'penalty_batch_id');
    }

    public function borrowingPenalty(): BelongsTo
    {
        return $this->belongsTo(BorrowingPenalty::class, 'borrowing_penalty_id');
    }
}
