<?php

namespace Modules\Authentication\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Authentication\Models\UserProfile;

/** @mixin UserProfile */
class UserProfileResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $photoPath = $this->photo_path ?: $this->profile_picture;

        return [
            'fullname' => $this->composedFullname(),
            'firstName' => $this->first_name,
            'middleName' => $this->middle_name,
            'lastName' => $this->last_name,
            'gender' => $this->gender,
            'dob' => $this->dob?->format('Y-m-d'),
            'profile_picture' => $photoPath,
            'photoPath' => $photoPath,
            'photoThumbPath' => $this->photo_thumb_path,
            'facultyId' => $this->faculty_id,
            'departmentId' => $this->department_id,
            'programmeId' => $this->programme_id,
            'yearOfStudy' => $this->year_of_study,
            'source' => $this->source ?? 'manual',
            'coreSyncedAt' => $this->core_synced_at?->toIso8601String(),
            'faculty' => $this->whenLoaded('faculty', fn () => $this->faculty ? [
                'id' => $this->faculty->id,
                'code' => $this->faculty->code,
                'nameEn' => $this->faculty->name_en,
            ] : null),
            'department' => $this->whenLoaded('department', fn () => $this->department ? [
                'id' => $this->department->id,
                'code' => $this->department->code,
                'nameEn' => $this->department->name_en,
            ] : null),
            'programme' => $this->whenLoaded('programme', fn () => $this->programme ? [
                'id' => $this->programme->id,
                'code' => $this->programme->code,
                'nameEn' => $this->programme->name_en,
            ] : null),
        ];
    }
}
