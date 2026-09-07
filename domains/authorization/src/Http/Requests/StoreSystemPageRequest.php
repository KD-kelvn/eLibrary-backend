<?php

namespace Modules\Authorization\Http\Requests;

use App\Support\SchemaTable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Authorization\Models\SystemPage;

class StoreSystemPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SystemPage::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'code' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique(SchemaTable::forValidation('auth.system_pages'), 'code')],
            'url' => ['nullable', 'string', 'max:255'],
            'is_public' => ['nullable', 'boolean'],
        ];
    }
}
