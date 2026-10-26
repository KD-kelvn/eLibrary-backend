<?php

namespace Modules\Academic\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Academic\Models\Course;

/**
 * @mixin Course
 */
class CourseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'programmeId' => $this->programme_id,
            'code' => $this->code,
            'nameEn' => $this->name_en,
            'nameSw' => $this->name_sw,
            'isActive' => $this->is_active,
            'programme' => ProgrammeResource::make($this->whenLoaded('programme')),
            'createdAt' => $this->whenNotNull($this->created_at),
            'updatedAt' => $this->whenNotNull($this->updated_at),
        ];
    }
}
