<?php

namespace Modules\Authorization\Http\Requests;

use App\Support\SchemaTable;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Authorization\Models\SystemPageRole;

class StoreSystemPageRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SystemPageRole::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'system_page_id' => ['required', 'integer', 'exists:'.SchemaTable::forValidation('auth.system_pages').',id'],
            'role_id' => ['required', 'integer', 'exists:'.SchemaTable::forValidation('auth.roles').',id'],
        ];
    }
}
