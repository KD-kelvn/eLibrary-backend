<?php

namespace Modules\Academic\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AcademicServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        Route::middleware('api')
            ->prefix('api/academic')
            ->group(__DIR__.'/../../api-routes/academic-routes.php');
    }
}
