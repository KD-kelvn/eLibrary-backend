<?php

namespace Modules\BookBorrowing\Providers;

use Illuminate\Support\ServiceProvider;

class BookBorrowingServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
