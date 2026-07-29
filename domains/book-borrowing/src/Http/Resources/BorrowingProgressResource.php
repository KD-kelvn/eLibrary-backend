<?php

namespace Modules\BookBorrowing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\BookBorrowing\Models\BorrowingProgress;

/**
 * @mixin BorrowingProgress
 */
class BorrowingProgressResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'borrowingRequestId' => $this->borrowing_request_id,
            'remarks' => $this->remarks,
            'statusCode' => $this->status_code,
            'process' => BorrowingProcessResource::make($this->whenLoaded('process')),
            'nextProcess' => BorrowingProcessResource::make($this->whenLoaded('nextProcess')),
            'senderRole' => $this->whenLoaded('senderRole', fn () => [
                'id' => $this->senderRole?->id,
                'name' => $this->senderRole?->name,
                'code' => $this->senderRole?->code,
            ]),
            'receiverRole' => $this->whenLoaded('receiverRole', fn () => [
                'id' => $this->receiverRole?->id,
                'name' => $this->receiverRole?->name,
                'code' => $this->receiverRole?->code,
            ]),
            'createdAt' => $this->whenNotNull($this->created_at),
            'updatedAt' => $this->whenNotNull($this->updated_at),
        ];
    }
}
