<?php

namespace Modules\Settings\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Settings\Models\Branding;
use Modules\Settings\Policies\BrandingPolicy;

class SettingsServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        Gate::policy(Branding::class, BrandingPolicy::class);

        Route::middleware('api')
            ->prefix('api/settings')
            ->group(__DIR__.'/../../api-routes/settings-routes.php');
    }
}
