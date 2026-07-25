<?php

namespace Modules\BookReading\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\BookReading\Models\ReadingHistory;

class IndexReadingHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', ReadingHistory::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', Rule::in(['physical', 'digital'])],
            'days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
