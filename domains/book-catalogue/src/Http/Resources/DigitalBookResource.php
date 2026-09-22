<?php

namespace Modules\BookCatalogue\Http\Resources;

use App\Support\PublicStorageUrl;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use Modules\BookCatalogue\Models\DigitalBook;

/**
 * @mixin DigitalBook
 */
class DigitalBookResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bookDetailId' => $this->book_detail_id,
            'file' => [
                'cover' => $this->resolveCoverUrl(),
                // Storage path is never exposed — pages stream via /books/{id}/pages/{n}.
                'name' => $this->file_name,
                'type' => $this->file_type,
                'size' => $this->file_size,
            ],
            'format' => [
                'value' => $this->format?->value,
                'label' => $this->format?->label(),
                'mimeType' => $this->format?->mimeType(),
            ],
            'isDownloadable' => $this->is_downloadable,
            'isActive' => $this->is_active,
            'checksum' => $this->checksum,
            'bookDetail' => BookDetailSummaryResource::make($this->whenLoaded('bookDetail')),
            'createdAt' => $this->whenNotNull($this->created_at),
            'updatedAt' => $this->whenNotNull($this->updated_at),
        ];
    }

    protected function resolveCoverUrl(): ?string
    {
        return PublicStorageUrl::make($this->file_cover);
    }
}
