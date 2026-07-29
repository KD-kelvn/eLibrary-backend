<?php

namespace Modules\BookBorrowing\Models;

use App\Models\BaseModelWithAudits;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\BookBorrowing\Enums\PenaltyBatchStatusEnum;

class PenaltyBatch extends BaseModelWithAudits
{
    protected $table = 'book_borrowing.penalty_batches';

    protected $fillable = [
        'borrowing_request_id',
        'book_type',
        'book_type_id',
        'batch_no',
        'cost_per_day',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'cost_per_day' => 'decimal:2',
            'status' => PenaltyBatchStatusEnum::class,
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

    public function penalties(): HasMany
    {
        return $this->hasMany(BorrowingPenalty::class, 'penalty_batch_id');
    }

    public function stoppedPenalty(): HasOne
    {
        return $this->hasOne(StoppedPenalty::class, 'penalty_batch_id');
    }
}
