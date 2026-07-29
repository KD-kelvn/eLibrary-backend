<?php

namespace Modules\BookBorrowing\Models;

use App\Models\BaseModelWithAudits;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BorrowingPenalty extends BaseModelWithAudits
{
    protected $table = 'book_borrowing.borrowing_penalties';

    protected $fillable = [
        'penalty_batch_id',
        'borrowing_request_id',
        'book_type',
        'book_type_id',
        'penalty_date',
        'bill_no',
        'control_no',
        'is_paid',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'penalty_date' => 'date',
            'is_paid' => 'boolean',
        ];
    }

    public function penaltyBatch(): BelongsTo
    {
        return $this->belongsTo(PenaltyBatch::class, 'penalty_batch_id');
    }

    public function borrowingRequest(): BelongsTo
    {
        return $this->belongsTo(BorrowingRequest::class, 'borrowing_request_id');
    }

    public function bookType(): MorphTo
    {
        return $this->morphTo();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PenaltyPayment::class, 'borrowing_penalty_id');
    }

    public function stoppedPenalty(): HasOne
    {
        return $this->hasOne(StoppedPenalty::class, 'borrowing_penalty_id');
    }
}
