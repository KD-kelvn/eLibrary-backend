<?php

namespace Modules\Authorization\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Authorization\Models\RevokedRole;
use Modules\Authorization\Models\Role;
use Modules\Authorization\Models\UserRole;
use Modules\Authorization\Policies\RevokedRolePolicy;
use Modules\Authorization\Policies\RolePolicy;
use Modules\Authorization\Policies\UserRolePolicy;

class AuthorizationServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->registerPolicies();

        Route::middleware('api')
            ->prefix('api/authorization')
            ->group(__DIR__.'/../../routes/authorization-routes.php');
    }

    protected function registerPolicies(): void
    {
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(UserRole::class, UserRolePolicy::class);
        Gate::policy(RevokedRole::class, RevokedRolePolicy::class);
    }
}
