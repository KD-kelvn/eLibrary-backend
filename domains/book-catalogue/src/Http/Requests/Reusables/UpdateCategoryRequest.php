<?php

namespace Modules\BookCatalogue\Http\Requests\Reusables;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\BookCatalogue\Models\Category;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $category = Category::query()->find($this->route('category'));

        return $category && $this->user()?->can('update', $category);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $categoryId = $this->route('category');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('book_catalog.categories', 'code')->ignore($categoryId),
            ],
        ];
    }
}
