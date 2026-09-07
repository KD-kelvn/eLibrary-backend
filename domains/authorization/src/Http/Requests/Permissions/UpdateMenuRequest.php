<?php

namespace Modules\Authorization\Http\Requests\Permissions;

use App\Support\SchemaTable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Authorization\Models\Menu;

class UpdateMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        $menu = Menu::query()->find($this->route('menu'));

        return $menu && ($this->user()?->can('update', $menu) ?? false);
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique(SchemaTable::forValidation('auth.menus'), 'code')->ignore($this->route('menu')),
            ],
            'icon_code' => ['sometimes', 'nullable', 'string', 'max:100'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_public' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
