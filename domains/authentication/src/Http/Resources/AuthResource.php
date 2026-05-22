<?php

namespace Modules\Authentication\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'user' => UserResource::make($this->resource['user']),
            'access_token' => $this->resource['access_token'],
            'token_type' => $this->resource['token_type'],
        ];
    }
}
