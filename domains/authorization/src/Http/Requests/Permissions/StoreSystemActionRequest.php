<?php

namespace Modules\Authorization\Http\Requests\Permissions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Authorization\Models\SystemAction;

class StoreSystemActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SystemAction::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'menu_item_id' => ['nullable', 'integer', 'exists:auth.menu_items,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'code' => ['required', 'string', 'max:150', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('auth.system_actions', 'code')],
            'action_type' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
