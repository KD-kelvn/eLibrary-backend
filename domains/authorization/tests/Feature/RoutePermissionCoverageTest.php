<?php

namespace Modules\Authorization\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Authorization\Http\Middleware\EnsurePermission;
use Modules\Authorization\Support\RoutePermissionResolver;
use Tests\TestCase;

/**
 * NFR-01 / TC-RBAC-02 style: every API route is public-allowlisted or has permission middleware + map entry.
 */
class RoutePermissionCoverageTest extends TestCase
{
    public function test_every_api_route_is_public_or_permission_mapped(): void
    {
        $public = collect(config('authorization.public_routes', []))->all();

        $resolver = app(RoutePermissionResolver::class);
        $missingMiddleware = [];
        $unmapped = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            if (! str_starts_with($uri, 'api/') && $uri !== 'up') {
                continue;
            }

            foreach ($route->methods() as $method) {
                if (in_array($method, ['HEAD', 'OPTIONS'], true)) {
                    continue;
                }

                $path = ltrim($uri, '/');
                $key = strtoupper($method).' '.$path;

                if (in_array($key, $public, true)) {
                    $this->assertFalse(
                        $this->routeHasPermissionMiddleware($route),
                        "Public route [{$key}] must not use permission middleware.",
                    );

                    continue;
                }

                if (! $this->routeHasPermissionMiddleware($route)) {
                    $missingMiddleware[] = $key;

                    continue;
                }

                $request = Request::create('/'.$path, $method);
                if ($resolver->codesFor($request) === null) {
                    $unmapped[] = $key;
                }
            }
        }

        $this->assertSame([], $missingMiddleware, 'Routes missing permission middleware: '.implode(', ', $missingMiddleware));
        $this->assertSame([], $unmapped, 'Routes not in config/authorization.php map: '.implode(', ', $unmapped));
    }

    protected function routeHasPermissionMiddleware(\Illuminate\Routing\Route $route): bool
    {
        $middleware = $route->gatherMiddleware();

        foreach ($middleware as $entry) {
            $name = is_string($entry) ? explode(':', $entry, 2)[0] : $entry;
            if ($name === 'permission' || $name === EnsurePermission::class) {
                return true;
            }
        }

        return false;
    }
}
