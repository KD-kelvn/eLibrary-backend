<?php

namespace Modules\Integrations\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Integrations\Models\SyncRun;

/** @mixin SyncRun */
class SyncRunResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'mode' => $this->mode,
            'status' => $this->status,
            'idempotencyKey' => $this->idempotency_key,
            'cursor' => $this->cursor,
            'counts' => [
                'created' => $this->created_count,
                'updated' => $this->updated_count,
                'deactivated' => $this->deactivated_count,
                'unchanged' => $this->unchanged_count,
                'failed' => $this->failed_count,
            ],
            'errorSummary' => $this->error_summary,
            'startedAt' => $this->started_at,
            'finishedAt' => $this->finished_at,
            'createdAt' => $this->created_at,
            'items' => SyncRunItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
