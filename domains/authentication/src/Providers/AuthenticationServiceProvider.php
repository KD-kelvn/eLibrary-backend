<?php

namespace Modules\Authentication\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use Modules\Authentication\Models\PersonalAccessToken;
use Modules\Authentication\Models\User;
use Modules\Authentication\Policies\PersonalAccessTokenPolicy;
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

        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        // SMS channel (`sms` / `log_sms`) is registered by NotificationsServiceProvider (V1-006).
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(PersonalAccessToken::class, PersonalAccessTokenPolicy::class);

        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__.'/../../api-routes/authentication-routes.php');
    }
}
