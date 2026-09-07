<?php

namespace Modules\BookCatalogue\Http\Requests\Reusables;

use App\Support\SchemaTable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\BookCatalogue\Models\SubCategory;

class UpdateSubCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $subCategory = SubCategory::query()->find($this->route('sub_category'));

        return $subCategory && $this->user()?->can('update', $subCategory);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $subCategoryId = $this->route('sub_category');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique(SchemaTable::forValidation('book_catalog.sub_categories'), 'code')->ignore($subCategoryId),
            ],
        ];
    }
}
