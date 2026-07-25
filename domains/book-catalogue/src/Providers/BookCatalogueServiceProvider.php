<?php

namespace Modules\BookCatalogue\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\BookCatalogue\Models\Category;
use Modules\BookCatalogue\Models\Shelf;
use Modules\BookCatalogue\Models\SubCategory;
use Modules\BookCatalogue\Models\Tag;
use Modules\BookCatalogue\Policies\CategoryPolicy;
use Modules\BookCatalogue\Policies\ShelfPolicy;
use Modules\BookCatalogue\Policies\SubCategoryPolicy;
use Modules\BookCatalogue\Policies\TagPolicy;

class BookCatalogueServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        $this->registerPolicies();

        Route::middleware('api')
            ->prefix('api/book-catalogue')
            ->group(__DIR__.'/../../api-routes/book-catalogue-routes.php');
    }

    protected function registerPolicies(): void
    {
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(SubCategory::class, SubCategoryPolicy::class);
        Gate::policy(Tag::class, TagPolicy::class);
        Gate::policy(Shelf::class, ShelfPolicy::class);
    }
}
