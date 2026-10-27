<?php

namespace Modules\Settings\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Settings\Console\Commands\ActivatePendingSettingsCommand;
use Modules\Settings\Models\Branding;
use Modules\Settings\Models\LoginSlide;
use Modules\Settings\Policies\BrandingPolicy;
use Modules\Settings\Policies\LoginSlidePolicy;
use Modules\Settings\Services\ProviderSettingsService;
use Modules\Settings\Services\SettingsService;
use Modules\Settings\Support\CrossFieldSettingsValidator;
use Modules\Settings\Support\LibrarySettingsAuthorizer;
use Modules\Settings\Support\SettingsCache;
use Modules\Settings\Support\SettingsRegistry;

class SettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsRegistry::class);
        $this->app->singleton(SettingsCache::class);
        $this->app->singleton(SettingsService::class);
        $this->app->singleton(ProviderSettingsService::class);
        $this->app->singleton(LibrarySettingsAuthorizer::class);
        $this->app->singleton(CrossFieldSettingsValidator::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        Gate::policy(Branding::class, BrandingPolicy::class);
        Gate::policy(LoginSlide::class, LoginSlidePolicy::class);

        Route::middleware('api')
            ->prefix('api/settings')
            ->group(__DIR__.'/../../api-routes/settings-routes.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ActivatePendingSettingsCommand::class,
            ]);
        }
    }
}
