<?php

namespace Modules\BookCatalogue\Http\Resources;

use App\Support\PublicStorageUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\BookCatalogue\Models\BookDetail;

/**
 * Slim book card for member dashboard / favourites.
 *
 * @mixin BookDetail
 */
class MemberBookCardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $cover = $this->file_cover;
        if (! $cover && $this->relationLoaded('digitalBook')) {
            $cover = $this->digitalBook?->file_cover;
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'authors' => $this->authors,
            'edition' => $this->edition,
            'pages' => $this->pages,
            'cover' => PublicStorageUrl::make($cover),
            'typeCode' => [
                'value' => $this->type_code?->value,
                'label' => $this->type_code?->label(),
            ],
            'categories' => CategorySummaryResource::collection($this->whenLoaded('categories')),
            'tags' => TagSummaryResource::collection($this->whenLoaded('tags')),
            'isFavourite' => (bool) ($this->is_favourite ?? false),
        ];
    }
}
