<?php

namespace Modules\BookCatalogue\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use Modules\BookCatalogue\Models\BookDetail;

/**
 * @mixin BookDetail
 */
class BookDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'typeCode' => [
                'value' => $this->type_code?->value,
                'label' => $this->type_code?->label(),
            ],
            'title' => $this->title,
            'description' => $this->description,
            'authors' => $this->authors,
            'isbn' => $this->isbn,
            'publisher' => $this->publisher,
            'pubYear' => $this->pub_year,
            'edition' => $this->edition,
            'language' => $this->language,
            'pages' => $this->pages,
            'physicalBooks' => PhysicalBookResource::collection($this->whenLoaded('physicalBooks')),
            'digitalBook' => DigitalBookResource::make($this->whenLoaded('digitalBook')),
            'categories' => CategorySummaryResource::collection($this->whenLoaded('categories')),
            'subCategories' => SubCategorySummaryResource::collection($this->whenLoaded('subCategories')),
            'tags' => TagSummaryResource::collection($this->whenLoaded('tags')),
            'bookHasCategories' => BookHasCategoryResource::collection($this->whenLoaded('bookHasCategories')),
            'bookHasSubCategories' => BookHasSubCategoryResource::collection($this->whenLoaded('bookHasSubCategories')),
            'bookHasTags' => BookHasTagResource::collection($this->whenLoaded('bookHasTags')),
            'createdAt' => $this->whenNotNull($this->created_at),
            'updatedAt' => $this->whenNotNull($this->updated_at),
        ];
    }
}
