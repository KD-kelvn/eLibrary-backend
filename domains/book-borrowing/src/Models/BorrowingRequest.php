<?php

namespace Modules\BookBorrowing\Models;

use App\Models\BaseModelWithAudits;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Authentication\Models\User;
use Modules\BookCatalogue\Models\BookDetail;

class BorrowingRequest extends BaseModelWithAudits
{
    protected $table = 'book_borrowing.borrowing_requests';

    protected $fillable = [
        'user_id',
        'book_id',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(BookDetail::class, 'book_id');
    }

    /**
     * PhysicalBook | DigitalBook
     */
    public function bookType(): MorphTo
    {
        return $this->morphTo();
    }

    public function progresses(): HasMany
    {
        return $this->hasMany(BorrowingProgress::class, 'borrowing_request_id');
    }

    public function latestProgress(): HasOne
    {
        return $this->hasOne(BorrowingProgress::class, 'borrowing_request_id')->latestOfMany();
    }

    public function extensions(): HasMany
    {
        return $this->hasMany(BorrowingExtension::class, 'borrowing_request_id');
    }

    public function penaltyBatches(): HasMany
    {
        return $this->hasMany(PenaltyBatch::class, 'borrowing_request_id');
    }

    public function penalties(): HasMany
    {
        return $this->hasMany(BorrowingPenalty::class, 'borrowing_request_id');
    }
}
