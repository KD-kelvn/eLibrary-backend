<?php

namespace Modules\Notifications\Enums;

enum NotificationChannel: string
{
    case InApp = 'in_app';
    case Email = 'email';
    case Sms = 'sms';

    /**
     * Laravel notification channel driver name.
     */
    public function driver(): string
    {
        return match ($this) {
            self::InApp => 'database',
            self::Email => 'mail',
            self::Sms => 'log_sms',
        };
    }

    public function provider(): string
    {
        return match ($this) {
            self::InApp => 'database',
            self::Email => 'smtp',
            self::Sms => 'log_sms',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function tryFromDriver(string $driver): ?self
    {
        return match ($driver) {
            'database' => self::InApp,
            'mail' => self::Email,
            'log_sms', 'sms' => self::Sms,
            default => null,
        };
    }
}
