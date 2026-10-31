<?php

namespace Modules\Integrations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexSyncRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'mode' => ['nullable', 'string', 'in:push,pull'],
            'status' => ['nullable', 'string', 'in:queued,running,completed,failed'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
