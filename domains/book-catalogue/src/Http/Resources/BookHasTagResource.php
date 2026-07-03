<?php

namespace Modules\BookCatalogue\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use Modules\BookCatalogue\Models\BookHasTag;

/**
 * @mixin BookHasTag
 */
class BookHasTagResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bookDetailId' => $this->book_detail_id,
            'tagId' => $this->tag_id,
            'bookDetail' => BookDetailSummaryResource::make($this->whenLoaded('bookDetail')),
            'tag' => TagSummaryResource::make($this->whenLoaded('tag')),
            'createdAt' => $this->whenNotNull($this->created_at),
            'updatedAt' => $this->whenNotNull($this->updated_at),
        ];
    }
}
