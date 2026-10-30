<?php

namespace Modules\Authentication\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OtpSentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return array_filter([
            'challenge_id' => $this->resource['challenge_id'] ?? null,
            'channel' => $this->resource['channel'] ?? null,
            'via' => $this->resource['via'] ?? null,
            'purpose' => $this->resource['purpose'] ?? null,
            'expires_at' => $this->resource['expires_at'] ?? null,
            'code' => $this->resource['code'] ?? null,
        ], fn ($value) => $value !== null);
    }
}
