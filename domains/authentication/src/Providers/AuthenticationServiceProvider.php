<?php

namespace Modules\Authentication\Providers;

use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Authentication\Notifications\Channels\LogSmsChannel;

class AuthenticationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/authentication.php',
            'authentication',
        );
    }

    public function boot(): void
    {
        Notification::extend('log_sms', fn () => new LogSmsChannel);

        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__.'/../../api-routes/authentication-routes.php');
    }
}
