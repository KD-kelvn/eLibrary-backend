<?php

namespace Modules\Notifications\Sms;

use Modules\Notifications\Contracts\SmsProvider;
use Modules\Notifications\Sms\Drivers\BeemSmsProvider;
use Modules\Notifications\Sms\Drivers\InstitutionalSmsProvider;
use Modules\Notifications\Sms\Drivers\LogSmsProvider;
use Modules\Settings\Services\ProviderSettingsService;

class SmsProviderManager
{
    public function __construct(
        private readonly ProviderSettingsService $providers,
    ) {}

    public function driver(?string $name = null): SmsProvider
    {
        $config = $this->providers->smsConfig();
        $driver = $name ?? (string) ($config['driver'] ?? ProviderSettingsService::SMS_DRIVER_LOG);

        return match ($driver) {
            ProviderSettingsService::SMS_DRIVER_BEEM => new BeemSmsProvider(
                is_array($config['beem'] ?? null) ? $config['beem'] : [],
            ),
            ProviderSettingsService::SMS_DRIVER_INSTITUTIONAL => new InstitutionalSmsProvider(
                is_array($config['institutional'] ?? null) ? $config['institutional'] : [],
            ),
            default => new LogSmsProvider,
        };
    }

    public function activeName(): string
    {
        $config = $this->providers->smsConfig();
        $driver = (string) ($config['driver'] ?? ProviderSettingsService::SMS_DRIVER_LOG);

        return in_array($driver, ProviderSettingsService::SMS_DRIVERS, true)
            ? $driver
            : ProviderSettingsService::SMS_DRIVER_LOG;
    }
}
