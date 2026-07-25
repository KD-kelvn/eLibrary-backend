<?php

namespace Modules\BookCatalogue\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shapes any reusable lookup model (Category, SubCategory, Tag, Shelf)
 * into a form/filter option: { id, key, label, value }.
 */
class ReusableOptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->code,
            'label' => $this->name,
            'value' => $this->id,
        ];
    }
}
