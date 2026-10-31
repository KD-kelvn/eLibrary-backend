<?php

namespace Modules\Integrations\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Integrations\Models\SyncRunItem;

/** @mixin SyncRunItem */
class SyncRunItemResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'regNo' => $this->reg_no,
            'action' => $this->action,
            'userId' => $this->user_id,
            'error' => $this->error,
            'meta' => $this->meta,
        ];
    }
}
