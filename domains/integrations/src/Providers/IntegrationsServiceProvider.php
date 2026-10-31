<?php

namespace Modules\Integrations\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Integrations\Http\Middleware\VerifyUniversityHmac;

class IntegrationsServiceProvider extends ServiceProvider
{
    public function boot(Router $router): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        $router->aliasMiddleware('university.hmac', VerifyUniversityHmac::class);

        Route::middleware('api')
            ->prefix('api/integrations')
            ->group(__DIR__.'/../../api-routes/integrations-routes.php');
    }
}
