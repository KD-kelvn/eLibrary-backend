<?php

namespace Modules\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Settings\Models\Branding;

class UpdateBrandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $branding = Branding::query()->first() ?? new Branding;

        return $this->user()?->can('update', $branding) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'app_name' => ['sometimes', 'required', 'string', 'max:120'],
            'primary_color' => ['sometimes', 'required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['sometimes', 'required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'tertiary_color' => ['sometimes', 'required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'logo_dark' => ['nullable', 'image', 'max:2048'],
            'favicon' => ['nullable', 'image', 'max:1024'],
            'remove_logo' => ['sometimes', 'boolean'],
            'remove_logo_dark' => ['sometimes', 'boolean'],
            'remove_favicon' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $booleans = ['remove_logo', 'remove_logo_dark', 'remove_favicon'];

        foreach ($booleans as $field) {
            if ($this->has($field)) {
                $this->merge([
                    $field => filter_var($this->input($field), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
                ]);
            }
        }
    }
}
