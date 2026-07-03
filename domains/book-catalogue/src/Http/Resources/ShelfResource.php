<?php

namespace Modules\BookCatalogue\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use Modules\BookCatalogue\Models\Shelf;

/**
 * @mixin Shelf
 */
class ShelfResource extends JsonResource
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
            'code' => $this->code,
            'location' => $this->location,
            'number' => $this->number,
            'rack' => $this->rack,
            'physicalBooksCount' => $this->whenCounted('physicalBooks'),
            'physicalBooks' => PhysicalBookResource::collection($this->whenLoaded('physicalBooks')),
            'createdAt' => $this->whenNotNull($this->created_at),
            'updatedAt' => $this->whenNotNull($this->updated_at),
        ];
    }
}
