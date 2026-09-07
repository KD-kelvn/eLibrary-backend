<?php

namespace Modules\Authorization\Http\Requests\Permissions;

use App\Support\SchemaTable;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Authorization\Models\SystemAction;

class IndexSystemActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', SystemAction::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'menu_item_id' => ['nullable', 'integer', 'exists:'.SchemaTable::forValidation('auth.menu_items').',id'],
            'is_active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
