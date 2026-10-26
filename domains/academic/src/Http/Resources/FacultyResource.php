<?php

namespace Modules\Academic\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Academic\Models\Faculty;

/**
 * @mixin Faculty
 */
class FacultyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'nameEn' => $this->name_en,
            'nameSw' => $this->name_sw,
            'isActive' => $this->is_active,
            'departmentsCount' => $this->whenCounted('departments'),
            'createdAt' => $this->whenNotNull($this->created_at),
            'updatedAt' => $this->whenNotNull($this->updated_at),
        ];
    }
}
