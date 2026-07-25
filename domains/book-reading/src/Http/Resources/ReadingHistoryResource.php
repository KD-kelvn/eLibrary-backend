<?php

namespace Modules\BookReading\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Authentication\Http\Resources\UserResource;
use Modules\BookCatalogue\Http\Resources\BookDetailSummaryResource;

class ReadingHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student' => UserResource::make($this->whenLoaded('user')),
            'book' => BookDetailSummaryResource::make($this->whenLoaded('bookDetail')),
            'progressPercent' => (float) $this->progress_percent,
            'currentLocation' => $this->current_location,
            'durationSeconds' => $this->duration_seconds,
            'startedAt' => $this->started_at,
            'lastReadAt' => $this->last_read_at,
            'completedAt' => $this->completed_at,
            'device' => $this->device,
        ];
    }
}
