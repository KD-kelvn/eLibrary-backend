<?php

namespace Modules\Authorization\Http\Requests\Permissions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Authorization\Models\SystemAction;

class UpdateSystemActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $action = SystemAction::query()->find($this->route('system_action'));

        return $action && ($this->user()?->can('update', $action) ?? false);
    }

    public function rules(): array
    {
        return [
            'menu_item_id' => ['sometimes', 'nullable', 'integer', 'exists:auth.menu_items,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:150',
                'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('auth.system_actions', 'code')->ignore($this->route('system_action')),
            ],
            'action_type' => ['sometimes', 'nullable', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
