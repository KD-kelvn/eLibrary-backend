<?php

namespace Modules\Authentication\Providers;

use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Authentication\Notifications\Channels\LogSmsChannel;
use Modules\Authentication\Models\User;
use Modules\Authentication\Policies\UserPolicy;

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
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        Notification::extend('log_sms', fn () => new LogSmsChannel);
        Gate::policy(User::class, UserPolicy::class);

        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__.'/../../api-routes/authentication-routes.php');
    }
}
