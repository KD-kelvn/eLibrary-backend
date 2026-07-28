<?php

namespace Modules\Settings\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Settings\Models\LoginSlide;

/**
 * @mixin LoginSlide
 */
class LoginSlideResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'category' => $this->category,
            'image' => $this->imageUrl(),
            'externalImageUrl' => $this->external_image_url,
            'sortOrder' => $this->sort_order,
            'isActive' => $this->is_active,
            'updatedAt' => $this->whenNotNull($this->updated_at),
        ];
    }
}
