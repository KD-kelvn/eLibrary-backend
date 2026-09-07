<?php

namespace Modules\BookCatalogue\Http\Requests\Reusables;

use App\Support\SchemaTable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\BookCatalogue\Models\Tag;

class UpdateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        $tag = Tag::query()->find($this->route('tag'));

        return $tag && $this->user()?->can('update', $tag);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $tagId = $this->route('tag');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique(SchemaTable::forValidation('book_catalog.tags'), 'code')->ignore($tagId),
            ],
        ];
    }
}
