<?php

namespace Modules\BookBorrowing\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use Modules\Authentication\Http\Resources\UserResource;
use Modules\BookBorrowing\Models\BorrowingRequest;
use Modules\BookCatalogue\Http\Resources\BookDetailSummaryResource;
use Modules\BookCatalogue\Http\Resources\DigitalBookResource;
use Modules\BookCatalogue\Http\Resources\PhysicalBookResource;
use Modules\BookCatalogue\Models\DigitalBook;
use Modules\BookCatalogue\Models\PhysicalBook;

/**
 * @mixin BorrowingRequest
 */
class BorrowingRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => UserResource::make($this->whenLoaded('user')),
            'book' => BookDetailSummaryResource::make($this->whenLoaded('book')),
            'bookType' => $this->whenLoaded('bookType', function () {
                if ($this->bookType instanceof PhysicalBook) {
                    return [
                        'type' => 'physical',
                        'details' => PhysicalBookResource::make($this->bookType),
                    ];
                }

                if ($this->bookType instanceof DigitalBook) {
                    return [
                        'type' => 'digital',
                        'details' => DigitalBookResource::make($this->bookType),
                    ];
                }

                return null;
            }),
            'startDate' => $this->start_date,
            'endDate' => $this->end_date,
            'latestProgress' => BorrowingProgressResource::make($this->whenLoaded('latestProgress')),
            'progresses' => BorrowingProgressResource::collection($this->whenLoaded('progresses')),
            'extensions' => BorrowingExtensionResource::collection($this->whenLoaded('extensions')),
            'penaltyBatches' => PenaltyBatchResource::collection($this->whenLoaded('penaltyBatches')),
            'createdAt' => $this->whenNotNull($this->created_at),
            'updatedAt' => $this->whenNotNull($this->updated_at),
        ];
    }
}
