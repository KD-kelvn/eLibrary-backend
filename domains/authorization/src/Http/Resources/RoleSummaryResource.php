<?php

namespace Modules\Authorization\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use Modules\Authorization\Models\Role;

/**
 * @mixin Role
 */
class RoleSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'status' => [
                'value' => $this->status?->value,
                'label' => $this->status?->label(),
                'color' => $this->status?->color(),
                'icon' => $this->status?->icon(),
            ],
        ];
    }
}
