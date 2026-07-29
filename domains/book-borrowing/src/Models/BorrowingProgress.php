<?php

namespace Modules\BookBorrowing\Models;

use App\Models\BaseModelWithAudits;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Authorization\Models\Role;

class BorrowingProgress extends BaseModelWithAudits
{
    protected $table = 'book_borrowing.borrowing_progress';

    protected $fillable = [
        'borrowing_request_id',
        'process_id',
        'next_process_id',
        'remarks',
        'status_code',
        'sender_role_id',
        'receiver_role_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function borrowingRequest(): BelongsTo
    {
        return $this->belongsTo(BorrowingRequest::class, 'borrowing_request_id');
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(BorrowingProcess::class, 'process_id');
    }

    public function nextProcess(): BelongsTo
    {
        return $this->belongsTo(BorrowingProcess::class, 'next_process_id');
    }

    public function senderRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'sender_role_id');
    }

    public function receiverRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'receiver_role_id');
    }
}
