<?php

namespace Modules\BookReading\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TrackReadingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'history_id' => ['nullable', 'integer', 'exists:book_catalog.reading_histories,id'],
            'book_detail_id' => ['required_without:history_id', 'integer', 'exists:book_catalog.book_details,id'],
            'progress_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'current_location' => ['nullable', 'string', 'max:255'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'device' => ['nullable', 'string', 'max:255'],
            'completed' => ['nullable', 'boolean'],
        ];
    }
}
