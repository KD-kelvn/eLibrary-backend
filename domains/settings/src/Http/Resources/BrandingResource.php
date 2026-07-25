<?php

namespace Modules\Settings\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Settings\Models\Branding;

/**
 * @mixin Branding
 */
class BrandingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'appName' => $this->app_name,
            'primaryColor' => $this->primary_color,
            'secondaryColor' => $this->secondary_color,
            'tertiaryColor' => $this->tertiary_color,
            'logoUrl' => $this->getFirstMediaUrl('logo') ?: null,
            'logoDarkUrl' => $this->getFirstMediaUrl('logo_dark') ?: null,
            'faviconUrl' => $this->getFirstMediaUrl('favicon') ?: null,
            'updatedAt' => $this->whenNotNull($this->updated_at),
        ];
    }
}
