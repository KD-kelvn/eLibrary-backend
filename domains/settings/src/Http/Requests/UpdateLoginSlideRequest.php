<?php

namespace Modules\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Settings\Models\LoginSlide;

class UpdateLoginSlideRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var LoginSlide $slide */
        $slide = $this->route('loginSlide');

        return $this->user()?->can('update', $slide) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:180'],
            'category' => ['sometimes', 'required', 'string', 'max:80'],
            'external_image_url' => ['nullable', 'url', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'max:5120'],
            'remove_image' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['is_active', 'remove_image'] as $field) {
            if ($this->has($field)) {
                $this->merge([
                    $field => filter_var(
                        $this->input($field),
                        FILTER_VALIDATE_BOOLEAN,
                        FILTER_NULL_ON_FAILURE,
                    ),
                ]);
            }
        }

        if ($this->has('sort_order') && $this->input('sort_order') !== null && $this->input('sort_order') !== '') {
            $this->merge(['sort_order' => (int) $this->input('sort_order')]);
        }
    }
}
