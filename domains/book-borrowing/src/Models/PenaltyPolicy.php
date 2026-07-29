<?php

namespace Modules\BookBorrowing\Models;

use App\Models\BaseModelWithAudits;

class PenaltyPolicy extends BaseModelWithAudits
{
    protected $table = 'book_borrowing.penalty_policies';

    protected $fillable = [
        'name',
        'grace_days',
        'cost_per_day',
        'currency',
        'is_active',
        'effective_from',
        'description',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'grace_days' => 'integer',
            'cost_per_day' => 'decimal:2',
            'is_active' => 'boolean',
            'effective_from' => 'datetime',
        ];
    }
}
