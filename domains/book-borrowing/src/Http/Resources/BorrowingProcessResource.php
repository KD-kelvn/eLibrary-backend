<?php

namespace Modules\BookBorrowing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\BookBorrowing\Models\BorrowingProcess;

/**
 * @mixin BorrowingProcess
 */
class BorrowingProcessResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'indexNo' => $this->index_no,
            'statusName' => $this->status_name,
            'statusColor' => $this->status_color,
            'statusCode' => $this->status_code,
            'isFinal' => $this->is_final,
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
