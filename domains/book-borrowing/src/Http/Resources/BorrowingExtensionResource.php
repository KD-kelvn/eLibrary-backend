<?php

namespace Modules\BookBorrowing\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use Modules\BookBorrowing\Models\BorrowingExtension;
use Modules\BookCatalogue\Http\Resources\DigitalBookResource;
use Modules\BookCatalogue\Http\Resources\PhysicalBookResource;
use Modules\BookCatalogue\Models\DigitalBook;
use Modules\BookCatalogue\Models\PhysicalBook;

/**
 * @mixin BorrowingExtension
 */
class BorrowingExtensionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'borrowingRequestId' => $this->borrowing_request_id,
            'startDate' => $this->start_date,
            'endDate' => $this->end_date,
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
            'createdAt' => $this->whenNotNull($this->created_at),
            'updatedAt' => $this->whenNotNull($this->updated_at),
        ];
    }
}
