<?php

namespace Modules\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Authentication\Models\PersonalAccessToken;

class IndexSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', PersonalAccessToken::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:all,active,expired'],
            'user_id' => ['nullable', 'integer', 'exists:auth.users,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
