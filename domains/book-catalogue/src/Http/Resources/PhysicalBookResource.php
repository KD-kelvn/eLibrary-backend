<?php

namespace Modules\BookCatalogue\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\BookCatalogue\Enums\PhysicalBookCopyStatusEnum;
use Modules\BookCatalogue\Models\PhysicalBook;

/**
 * @mixin PhysicalBook
 */
class PhysicalBookResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bookDetailId' => $this->book_detail_id,
            'shelfId' => $this->shelf_id,
            'codeNo' => $this->code_no,
            'copies' => $this->copies,
            'availableCopies' => $this->whenLoaded(
                'copyRecords',
                fn () => $this->copyRecords
                    ->where('status', PhysicalBookCopyStatusEnum::Available)
                    ->count(),
            ),
            'onLoanCopies' => $this->whenLoaded(
                'copyRecords',
                fn () => $this->copyRecords
                    ->where('status', PhysicalBookCopyStatusEnum::OnLoan)
                    ->count(),
            ),
            'copyRecords' => PhysicalBookCopyResource::collection($this->whenLoaded('copyRecords')),
            'bookDetail' => BookDetailSummaryResource::make($this->whenLoaded('bookDetail')),
            'shelf' => ShelfSummaryResource::make($this->whenLoaded('shelf')),
            'createdAt' => $this->whenNotNull($this->created_at),
            'updatedAt' => $this->whenNotNull($this->updated_at),
        ];
    }
}
