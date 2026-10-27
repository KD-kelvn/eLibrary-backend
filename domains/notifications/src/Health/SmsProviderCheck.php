<?php

namespace Modules\Notifications\Health;

use Modules\Notifications\Sms\SmsProviderManager;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

class SmsProviderCheck extends Check
{
    public function run(): Result
    {
        $manager = app(SmsProviderManager::class);
        $driver = $manager->driver();
        $ping = $driver->ping();

        $result = Result::make()
            ->meta([
                'provider' => $ping->provider,
                ...$ping->meta,
            ]);

        if ($ping->ok) {
            return $result->ok($ping->message);
        }

        return $result->failed($ping->message);
    }
}
