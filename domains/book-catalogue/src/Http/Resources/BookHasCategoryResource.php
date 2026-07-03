<?php

namespace Modules\BookCatalogue\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use Modules\BookCatalogue\Models\BookHasCategory;

/**
 * @mixin BookHasCategory
 */
class BookHasCategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bookDetailId' => $this->book_detail_id,
            'categoryId' => $this->category_id,
            'bookDetail' => BookDetailSummaryResource::make($this->whenLoaded('bookDetail')),
            'category' => CategorySummaryResource::make($this->whenLoaded('category')),
            'createdAt' => $this->whenNotNull($this->created_at),
            'updatedAt' => $this->whenNotNull($this->updated_at),
        ];
    }
}
