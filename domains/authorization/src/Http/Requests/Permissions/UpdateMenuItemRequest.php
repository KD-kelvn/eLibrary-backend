<?php

namespace Modules\Authorization\Http\Requests\Permissions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Authorization\Models\MenuItem;

class UpdateMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $item = MenuItem::query()->find($this->route('menu_item'));

        return $item && ($this->user()?->can('update', $item) ?? false);
    }

    public function rules(): array
    {
        return [
            'menu_id' => ['sometimes', 'required', 'integer', 'exists:auth.menus,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('auth.menu_items', 'code')->ignore($this->route('menu_item')),
            ],
            'route' => ['sometimes', 'required', 'string', 'max:255'],
            'icon_code' => ['sometimes', 'nullable', 'string', 'max:100'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_public' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
