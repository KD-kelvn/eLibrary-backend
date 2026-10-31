<?php

namespace Modules\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Settings\Services\ProviderSettingsService;

class UpdateProviderSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sms' => ['sometimes', 'array'],
            'sms.driver' => ['sometimes', 'string', Rule::in(ProviderSettingsService::SMS_DRIVERS)],
            'sms.beem' => ['sometimes', 'array'],
            'sms.beem.url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'sms.beem.api_key' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sms.beem.secret_key' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sms.beem.sender_id' => ['sometimes', 'nullable', 'string', 'max:11'],
            'sms.beem.balance_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'sms.institutional' => ['sometimes', 'array'],
            'sms.institutional.base_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'sms.institutional.send_path' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sms.institutional.health_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'sms.institutional.api_key' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sms.institutional.api_secret' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sms.institutional.api_key_header' => ['sometimes', 'nullable', 'string', 'max:64'],
            'sms.institutional.auth_scheme' => ['sometimes', 'nullable', 'string', Rule::in(['header', 'bearer', 'basic'])],
            'sms.institutional.sender_id' => ['sometimes', 'nullable', 'string', 'max:32'],
            'sms.institutional.to_field' => ['sometimes', 'nullable', 'string', 'max:64'],
            'sms.institutional.message_field' => ['sometimes', 'nullable', 'string', 'max:64'],
            'sms.institutional.sender_field' => ['sometimes', 'nullable', 'string', 'max:64'],
            'university' => ['sometimes', 'array'],
            'university.base_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'university.students_path' => ['sometimes', 'nullable', 'string', 'max:255'],
            'university.api_key' => ['sometimes', 'nullable', 'string', 'max:255'],
            'university.api_key_header' => ['sometimes', 'nullable', 'string', 'max:64'],
            'university.auth_scheme' => ['sometimes', 'nullable', 'string', Rule::in(['bearer', 'header', 'none'])],
            'university.timeout' => ['sometimes', 'nullable', 'integer', 'min:5', 'max:120'],
            'university.hmac_secret' => ['sometimes', 'nullable', 'string', 'max:255'],
            'university.timestamp_tolerance_seconds' => ['sometimes', 'nullable', 'integer', 'min:60', 'max:3600'],
        ];
    }
}
