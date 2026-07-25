<?php

namespace Modules\Authorization\Http\Requests\Permissions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Authorization\Models\MenuItem;

class StoreMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', MenuItem::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'menu_id' => ['required', 'integer', 'exists:auth.menus,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'code' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('auth.menu_items', 'code')],
            'route' => ['required', 'string', 'max:255'],
            'icon_code' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_public' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
