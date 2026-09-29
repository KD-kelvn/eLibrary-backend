<?php

namespace Modules\BookBorrowing\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use Modules\BookBorrowing\Enums\BorrowingProcessCodeEnum;
use Modules\BookBorrowing\Models\BorrowingRequest;

/**
 * Compact borrowing row for the member dashboard.
 *
 * @mixin BorrowingRequest
 */
class MemberBorrowingSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $process = $this->latestProgress?->process;

        return [
            'id' => $this->id,
            'book' => [
                'id' => $this->book?->id,
                'title' => $this->book?->title,
            ],
            'startDate' => $this->start_date,
            'endDate' => $this->end_date,
            'isOverdue' => $this->latestProgress?->status_code === BorrowingProcessCodeEnum::Approved->value &&
                (bool) $this->end_date?->isPast(),
            'status' => [
                'code' => $this->latestProgress?->status_code,
                'label' => $process?->status_name,
                'color' => $process?->status_color,
            ],
        ];
    }
}
