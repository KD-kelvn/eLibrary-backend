<?php

namespace Modules\BookCatalogue\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\BookCatalogue\Models\BookDetail;

/**
 * @mixin BookDetail
 */
class BookDetailSummaryResource extends JsonResource
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
            'authors' => $this->authors,
            'isbn' => $this->isbn,
        ];
    }
}
