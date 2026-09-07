<?php

namespace Modules\Authorization\Http\Requests;

use App\Support\SchemaTable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Authorization\Models\SystemModule;

class StoreSystemModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SystemModule::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'code' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique(SchemaTable::forValidation('auth.system_modules'), 'code')],
            'icon_code' => ['nullable', 'string', 'max:100'],
            'bg_color' => ['nullable', 'string', 'max:50'],
            'landing_url' => ['nullable', 'string', 'max:255'],
        ];
    }
}
