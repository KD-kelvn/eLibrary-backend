<?php

namespace Modules\BookBorrowing\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\BookBorrowing\Models\StoppedPenalty;

/**
 * @mixin StoppedPenalty
 */
class StoppedPenaltyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'penaltyBatchId' => $this->penalty_batch_id,
            'borrowingPenaltyId' => $this->borrowing_penalty_id,
            'stoppedDate' => $this->stopped_date,
            'stoppedReason' => $this->stopped_reason,
            'createdAt' => $this->whenNotNull($this->created_at),
            'updatedAt' => $this->whenNotNull($this->updated_at),
        ];
    }
}
