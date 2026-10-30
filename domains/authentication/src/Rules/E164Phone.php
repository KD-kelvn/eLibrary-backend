<?php

namespace Modules\Authentication\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Authentication\Support\PhoneNumberNormalizer;

class E164Phone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $normalizer = app(PhoneNumberNormalizer::class);
        $normalized = $normalizer->normalize(is_string($value) ? $value : null);

        if ($normalized === null || ! $normalizer->isE164($normalized)) {
            $fail('The :attribute must be a valid phone number (E.164).');
        }
    }
}
