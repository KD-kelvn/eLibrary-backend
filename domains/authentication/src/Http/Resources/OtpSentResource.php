<?php

namespace Modules\Authentication\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OtpSentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'via' => $this->resource['via'],
            'purpose' => $this->resource['purpose'],
            'expires_at' => $this->resource['expires_at'],
            'code' => $this->resource['code'] ?? null,
        ];
    }
}
