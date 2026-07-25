<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\DatabaseConnectionCountCheck;
use Spatie\Health\Checks\Checks\DebugModeCheck;
use Spatie\Health\Checks\Checks\EnvironmentCheck;
use Spatie\Health\Checks\Checks\OptimizedAppCheck;
use Spatie\Health\Checks\Checks\QueueCheck;
use Spatie\Health\Checks\Checks\ScheduleCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $checks = [
            DatabaseCheck::new(),
            DatabaseConnectionCountCheck::new()
                ->warnWhenMoreConnectionsThan(50)
                ->failWhenMoreConnectionsThan(100),
            CacheCheck::new(),
            UsedDiskSpaceCheck::new()
                ->warnWhenUsedSpaceIsAbovePercentage(80)
                ->failWhenUsedSpaceIsAbovePercentage(90),
            ScheduleCheck::new()->heartbeatMaxAgeInMinutes(5),
            QueueCheck::new()->failWhenHealthJobTakesLongerThanMinutes(5),
        ];

        if ($this->app->isProduction()) {
            $checks = [
                ...$checks,
                DebugModeCheck::new(),
                EnvironmentCheck::new()->expectEnvironment('production'),
                OptimizedAppCheck::new(),
            ];
        } else {
            $checks = [
                ...$checks,
                EnvironmentCheck::new()->expectEnvironment((string) $this->app->environment()),
                DebugModeCheck::new()->expectedToBe((bool) config('app.debug')),
            ];
        }

        Health::checks($checks);
    }
}
