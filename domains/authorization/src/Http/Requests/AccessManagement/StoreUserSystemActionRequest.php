<?php

namespace Modules\Authorization\Http\Requests\AccessManagement;

use App\Support\SchemaTable;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserSystemActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasActiveRole('admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:'.SchemaTable::forValidation('auth.users').',id'],
            'system_action_id' => [
                'required',
                'integer',
                'exists:'.SchemaTable::forValidation('auth.system_actions').',id',
            ],
        ];
    }
}
