<?php

namespace Modules\BookCatalogue\Http\Requests\Reusables;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\BookCatalogue\Models\Tag;

class StoreTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Tag::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'code' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('book_catalog.tags', 'code')],
        ];
    }
}
