<?php

namespace Modules\Authentication\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

trait RespondsWithJson
{
    protected function success(JsonResource|array $data, string $message = '', int $status = 200): JsonResponse
    {
        $payload = $data instanceof JsonResource ? $data->resolve() : $data;

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $payload,
        ], $status);
    }
}
