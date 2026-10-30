<?php

namespace Modules\Authentication\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OtpOptionsResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'challenge_id' => $this->resource['challenge_id'],
            'channels' => $this->resource['channels'] ?? [],
        ];
    }
}
