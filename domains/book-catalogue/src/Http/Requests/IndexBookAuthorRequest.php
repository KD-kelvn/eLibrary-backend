<?php

namespace Modules\BookCatalogue\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\BookCatalogue\Models\BookDetail;

class IndexBookAuthorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', BookDetail::class) ?? false;
    }

    /**
     * Without `limit` the top authors (by active book count) are returned.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
