<?php

namespace Modules\BookReading\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\BookReading\Models\ReadingHistory;
use Modules\BookReading\Policies\ReadingHistoryPolicy;

class BookReadingServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        Gate::policy(ReadingHistory::class, ReadingHistoryPolicy::class);

        Route::middleware('api')
            ->prefix('api/book-reading')
            ->group(__DIR__.'/../../routes/book-reading-routes.php');
    }
}
