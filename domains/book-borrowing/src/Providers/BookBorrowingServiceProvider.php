<?php

namespace Modules\BookBorrowing\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\BookBorrowing\Console\Commands\CreatePenaltyBatchesCommand;
use Modules\BookBorrowing\Console\Commands\GenerateDailyPenaltiesCommand;
use Modules\BookBorrowing\Models\BorrowingProcess;
use Modules\BookBorrowing\Models\BorrowingRequest;
use Modules\BookBorrowing\Models\PenaltyBatch;
use Modules\BookBorrowing\Models\PenaltyPolicy;
use Modules\BookBorrowing\Policies\BorrowingProcessPolicy;
use Modules\BookBorrowing\Policies\BorrowingRequestPolicy;
use Modules\BookBorrowing\Policies\PenaltyBatchPolicy;
use Modules\BookBorrowing\Policies\PenaltyPolicyPolicy;

class BookBorrowingServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        $this->registerPolicies();
        $this->registerRoutes();
        $this->registerCommands();
    }

    protected function registerPolicies(): void
    {
        Gate::policy(BorrowingProcess::class, BorrowingProcessPolicy::class);
        Gate::policy(PenaltyPolicy::class, PenaltyPolicyPolicy::class);
        Gate::policy(BorrowingRequest::class, BorrowingRequestPolicy::class);
        Gate::policy(PenaltyBatch::class, PenaltyBatchPolicy::class);
    }

    protected function registerRoutes(): void
    {
        Route::middleware('api')
            ->prefix('api/book-borrowing')
            ->group(__DIR__.'/../../routes/book-borrowing-routes.php');
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CreatePenaltyBatchesCommand::class,
                GenerateDailyPenaltiesCommand::class,
            ]);
        }
    }
}
