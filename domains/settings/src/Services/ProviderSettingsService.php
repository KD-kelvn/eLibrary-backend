<?php

namespace Modules\Settings\Services;

use App\Support\AdminActivity;
use Modules\Settings\Models\ProviderSetting;

/**
 * Integration credentials under settings.providers (FR-SET-08).
 * Secrets are write-only — GET returns *_set flags, never raw values.
 */
class ProviderSettingsService
{
    public const GROUP_SMS = 'sms';

    public const SMS_DRIVER_LOG = 'log';

    public const SMS_DRIVER_BEEM = 'beem';

    public const SMS_DRIVER_INSTITUTIONAL = 'institutional';

    /**
     * @var list<string>
     */
    public const SMS_DRIVERS = [
        self::SMS_DRIVER_LOG,
        self::SMS_DRIVER_BEEM,
        self::SMS_DRIVER_INSTITUTIONAL,
    ];

    /**
     * @var list<string>
     */
    private const SMS_SECRET_KEYS = ['api_key', 'secret_key', 'api_secret'];

    /**
     * @return array{sms: array<string, mixed>}
     */
    public function publicView(): array
    {
        return [
            'sms' => $this->redactSms($this->smsConfig()),
        ];
    }

    /**
     * Full SMS config including secrets (server-side only).
     *
     * @return array<string, mixed>
     */
    public function smsConfig(): array
    {
        $stored = $this->raw(self::GROUP_SMS);

        return $this->mergeSmsDefaults($stored);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{sms: array<string, mixed>}
     */
    public function updateSms(array $input, ?int $updatedBy = null): array
    {
        $current = $this->smsConfig();
        $merged = $this->mergeSmsUpdate($current, $input);

        $driver = (string) ($merged['driver'] ?? self::SMS_DRIVER_LOG);
        if (! in_array($driver, self::SMS_DRIVERS, true)) {
            abort(422, 'Invalid SMS driver.');
        }
        $merged['driver'] = $driver;

        $row = ProviderSetting::query()->updateOrCreate(
            ['group' => self::GROUP_SMS],
            [
                'config' => $merged,
                'updated_by' => $updatedBy ?? auth()->id(),
            ],
        );

        AdminActivity::log(
            'Provider settings updated',
            $row,
            [
                'group' => self::GROUP_SMS,
                'driver' => $driver,
                'secrets_touched' => $this->touchedSecrets($input),
            ],
        );

        return $this->publicView();
    }

    /**
     * @return array<string, mixed>
     */
    protected function raw(string $group): array
    {
        $row = ProviderSetting::query()->where('group', $group)->first();
        $config = $row?->config;

        return is_array($config) ? $config : [];
    }

    /**
     * Config (`services.sms.*`) already carries env defaults; stored provider_settings override.
     *
     * @param  array<string, mixed>  $stored
     * @return array<string, mixed>
     */
    protected function mergeSmsDefaults(array $stored): array
    {
        $beem = config('services.sms.beem');
        $institutional = config('services.sms.institutional');

        return [
            'driver' => (string) ($stored['driver'] ?? config('services.sms.driver')),
            'beem' => array_merge(
                is_array($beem) ? $beem : [],
                is_array($stored['beem'] ?? null) ? $stored['beem'] : [],
            ),
            'institutional' => array_merge(
                is_array($institutional) ? $institutional : [],
                is_array($stored['institutional'] ?? null) ? $stored['institutional'] : [],
            ),
            'log' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    protected function mergeSmsUpdate(array $current, array $input): array
    {
        if (array_key_exists('driver', $input) && is_string($input['driver'])) {
            $current['driver'] = $input['driver'];
        }

        foreach (['beem', 'institutional'] as $section) {
            if (! isset($input[$section]) || ! is_array($input[$section])) {
                continue;
            }

            $sectionCurrent = is_array($current[$section] ?? null) ? $current[$section] : [];
            foreach ($input[$section] as $key => $value) {
                if (! is_string($key)) {
                    continue;
                }

                // Blank secret = keep existing (write-only semantics).
                if (in_array($key, self::SMS_SECRET_KEYS, true) && ($value === null || $value === '')) {
                    continue;
                }

                if (is_scalar($value) || $value === null) {
                    $sectionCurrent[$key] = $value === null ? '' : (string) $value;
                }
            }
            $current[$section] = $sectionCurrent;
        }

        return $current;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    protected function redactSms(array $config): array
    {
        $out = [
            'driver' => $config['driver'] ?? self::SMS_DRIVER_LOG,
            'drivers' => self::SMS_DRIVERS,
            'beem' => $this->redactSection(is_array($config['beem'] ?? null) ? $config['beem'] : []),
            'institutional' => $this->redactSection(is_array($config['institutional'] ?? null) ? $config['institutional'] : []),
            'log' => [],
        ];

        return $out;
    }

    /**
     * @param  array<string, mixed>  $section
     * @return array<string, mixed>
     */
    protected function redactSection(array $section): array
    {
        $out = [];
        foreach ($section as $key => $value) {
            if (in_array($key, self::SMS_SECRET_KEYS, true)) {
                $out[$key.'_set'] = is_string($value) && $value !== '';

                continue;
            }
            $out[$key] = $value;
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return list<string>
     */
    protected function touchedSecrets(array $input): array
    {
        $touched = [];
        foreach (['beem', 'institutional'] as $section) {
            if (! isset($input[$section]) || ! is_array($input[$section])) {
                continue;
            }
            foreach (self::SMS_SECRET_KEYS as $secret) {
                $value = $input[$section][$secret] ?? null;
                if (is_string($value) && $value !== '') {
                    $touched[] = $section.'.'.$secret;
                }
            }
        }

        return $touched;
    }
}
