<?php

namespace Modules\BookBorrowing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\BookBorrowing\Models\PenaltyBatch;

/**
 * Compact pending penalty row for the member dashboard.
 *
 * @mixin PenaltyBatch
 */
class MemberPenaltySummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $days = (int) ($this->unpaid_days_count ?? 0);
        $book = $this->borrowingRequest?->book;

        return [
            'id' => $this->id,
            'batchNo' => $this->batch_no,
            'book' => [
                'id' => $book?->id,
                'title' => $book?->title,
            ],
            'days' => $days,
            'costPerDay' => (float) $this->cost_per_day,
            'amount' => round($days * (float) $this->cost_per_day, 2),
        ];
    }
}
