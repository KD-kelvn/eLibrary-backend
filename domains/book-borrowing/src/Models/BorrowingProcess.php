<?php

namespace Modules\BookBorrowing\Models;

use App\Models\BaseModelWithAudits;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Authorization\Models\Role;

class BorrowingProcess extends BaseModelWithAudits
{
    protected $table = 'book_borrowing.borrowing_processes';

    protected $fillable = [
        'name',
        'description',
        'index_no',
        'status_name',
        'status_color',
        'status_code',
        'sender_role_id',
        'receiver_role_id',
        'is_final',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'index_no' => 'integer',
            'is_final' => 'boolean',
        ];
    }

    public function senderRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'sender_role_id');
    }

    public function receiverRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'receiver_role_id');
    }

    public function progresses(): HasMany
    {
        return $this->hasMany(BorrowingProgress::class, 'process_id');
    }

    public function currentProgress(): HasOne
    {
        return $this->hasOne(BorrowingProgress::class, 'process_id')->latestOfMany();
    }
}
