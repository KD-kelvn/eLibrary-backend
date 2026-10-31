<?php

namespace Modules\Integrations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowSyncRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
