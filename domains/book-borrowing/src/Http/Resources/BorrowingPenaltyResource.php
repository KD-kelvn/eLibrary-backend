<?php

namespace Modules\BookBorrowing\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\BookBorrowing\Models\BorrowingPenalty;

/**
 * @mixin BorrowingPenalty
 */
class BorrowingPenaltyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'penaltyBatchId' => $this->penalty_batch_id,
            'borrowingRequestId' => $this->borrowing_request_id,
            'penaltyDate' => $this->penalty_date,
            'billNo' => $this->bill_no,
            'controlNo' => $this->control_no,
            'isPaid' => $this->is_paid,
            'payments' => PenaltyPaymentResource::collection($this->whenLoaded('payments')),
            'stoppedPenalty' => StoppedPenaltyResource::make($this->whenLoaded('stoppedPenalty')),
            'createdAt' => $this->whenNotNull($this->created_at),
            'updatedAt' => $this->whenNotNull($this->updated_at),
        ];
    }
}
