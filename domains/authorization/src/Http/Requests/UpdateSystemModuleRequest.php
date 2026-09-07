<?php

namespace Modules\Authorization\Http\Requests;

use App\Support\SchemaTable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Authorization\Models\SystemModule;

class UpdateSystemModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $module = SystemModule::query()->find($this->route('system_module'));

        return $module && $this->user()?->can('update', $module);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $moduleId = $this->route('system_module');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique(SchemaTable::forValidation('auth.system_modules'), 'code')->ignore($moduleId),
            ],
            'icon_code' => ['sometimes', 'nullable', 'string', 'max:100'],
            'bg_color' => ['sometimes', 'nullable', 'string', 'max:50'],
            'landing_url' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
