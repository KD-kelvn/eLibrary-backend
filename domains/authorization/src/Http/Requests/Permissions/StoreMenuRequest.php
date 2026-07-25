<?php

namespace Modules\Authorization\Http\Requests\Permissions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Authorization\Models\Menu;

class StoreMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Menu::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'code' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('auth.menus', 'code')],
            'icon_code' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_public' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
