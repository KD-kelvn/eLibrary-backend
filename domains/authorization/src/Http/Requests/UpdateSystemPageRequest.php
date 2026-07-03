<?php

namespace Modules\Authorization\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Authorization\Models\SystemPage;

class UpdateSystemPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $page = SystemPage::query()->find($this->route('system_page'));

        return $page && $this->user()?->can('update', $page);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $pageId = $this->route('system_page');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('auth.system_pages', 'code')->ignore($pageId),
            ],
            'url' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_public' => ['sometimes', 'boolean'],
        ];
    }
}
