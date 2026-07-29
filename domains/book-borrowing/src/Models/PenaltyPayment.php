<?php

namespace Modules\BookBorrowing\Models;

use App\Models\BaseModelWithAudits;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenaltyPayment extends BaseModelWithAudits
{
    protected $table = 'book_borrowing.penalty_payments';

    protected $fillable = [
        'borrowing_penalty_id',
        'payment_date',
        'payment_amount',
        'payment_method',
        'payer_mobile',
        'payer_acc',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'datetime',
            'payment_amount' => 'decimal:2',
        ];
    }

    public function borrowingPenalty(): BelongsTo
    {
        return $this->belongsTo(BorrowingPenalty::class, 'borrowing_penalty_id');
    }
}
