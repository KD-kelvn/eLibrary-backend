<?php

namespace Modules\BookBorrowing\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use Modules\BookBorrowing\Models\PenaltyBatch;

/**
 * @mixin PenaltyBatch
 */
class PenaltyBatchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'borrowingRequestId' => $this->borrowing_request_id,
            'borrowingRequest' => BorrowingRequestResource::make($this->whenLoaded('borrowingRequest')),
            'batchNo' => $this->batch_no,
            'costPerDay' => (float) $this->cost_per_day,
            'penaltiesCount' => $this->whenCounted('penalties'),
            'status' => [
                'value' => $this->status?->value,
                'label' => $this->status?->label(),
                'color' => $this->status?->color(),
            ],
            'penalties' => BorrowingPenaltyResource::collection($this->whenLoaded('penalties')),
            'stoppedPenalty' => StoppedPenaltyResource::make($this->whenLoaded('stoppedPenalty')),
            'createdAt' => $this->whenNotNull($this->created_at),
            'updatedAt' => $this->whenNotNull($this->updated_at),
        ];
    }
}
