<?php

namespace Modules\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetUserBlockedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasActiveRole('admin') ?? false;
    }

    public function rules(): array
    {
        return ['is_blocked' => ['required', 'boolean']];
    }
}
