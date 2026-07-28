<?php

namespace Modules\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Settings\Models\LoginSlide;

class StoreLoginSlideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', LoginSlide::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'category' => ['required', 'string', 'max:80'],
            'external_image_url' => ['nullable', 'url', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['sometimes', 'boolean'],
            'image' => [
                'nullable',
                'image',
                'max:5120',
                'required_without:external_image_url',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var(
                    $this->input('is_active'),
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE,
                ),
            ]);
        }

        if ($this->has('sort_order') && $this->input('sort_order') !== null && $this->input('sort_order') !== '') {
            $this->merge(['sort_order' => (int) $this->input('sort_order')]);
        }
    }
}
