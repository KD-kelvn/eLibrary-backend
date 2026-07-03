<?php

namespace Modules\BookCatalogue\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use Modules\BookCatalogue\Models\BookHasSubCategory;

/**
 * @mixin BookHasSubCategory
 */
class BookHasSubCategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bookDetailId' => $this->book_detail_id,
            'subCategoryId' => $this->sub_category_id,
            'bookDetail' => BookDetailSummaryResource::make($this->whenLoaded('bookDetail')),
            'subCategory' => SubCategorySummaryResource::make($this->whenLoaded('subCategory')),
            'createdAt' => $this->whenNotNull($this->created_at),
            'updatedAt' => $this->whenNotNull($this->updated_at),
        ];
    }
}
