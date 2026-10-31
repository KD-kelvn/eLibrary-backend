<?php

namespace Modules\Integrations\Services;

use Modules\Settings\Models\ProviderSetting;

/**
 * University integration settings: env defaults + provider_settings.university overrides.
 */
class UniversityProviderConfig
{
    public const GROUP = 'university';

    /**
     * Inbound push HMAC + skew (also used by UniversityHmacVerifier).
     *
     * @return array{hmac_secret: string, timestamp_tolerance_seconds: int}
     */
    public function pushAuth(): array
    {
        $stored = $this->stored();

        return [
            'hmac_secret' => (string) ($stored['hmac_secret'] ?? config('services.university.hmac_secret', '')),
            'timestamp_tolerance_seconds' => (int) ($stored['timestamp_tolerance_seconds']
                ?? config('services.university.timestamp_tolerance_seconds', 300)),
        ];
    }

    /**
     * Outbound pull client settings.
     *
     * @return array<string, mixed>
     */
    public function pullConfig(): array
    {
        $stored = $this->stored();
        $env = config('services.university', []);

        return [
            'base_url' => (string) ($stored['base_url'] ?? $env['base_url'] ?? ''),
            'students_path' => (string) ($stored['students_path'] ?? $env['students_path'] ?? '/students'),
            'api_key' => (string) ($stored['api_key'] ?? $env['api_key'] ?? ''),
            'api_key_header' => (string) ($stored['api_key_header'] ?? $env['api_key_header'] ?? 'X-Api-Key'),
            'auth_scheme' => (string) ($stored['auth_scheme'] ?? $env['auth_scheme'] ?? 'bearer'),
            'timeout' => (int) ($stored['timeout'] ?? $env['timeout'] ?? 30),
            'hmac_secret' => (string) ($stored['hmac_secret'] ?? $env['hmac_secret'] ?? ''),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function stored(): array
    {
        $row = ProviderSetting::query()->where('group', self::GROUP)->first();
        $config = $row?->config;

        return is_array($config) ? $config : [];
    }
}
