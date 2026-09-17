<?php

namespace Modules\Authorization\Http\Requests\AccessManagement;

use App\Support\SchemaTable;
use Illuminate\Foundation\Http\FormRequest;

class SyncUserSystemActionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasActiveRole('admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'action_ids' => ['required', 'array'],
            'action_ids.*' => [
                'integer',
                'exists:'.SchemaTable::forValidation('auth.system_actions').',id',
            ],
        ];
    }
}
