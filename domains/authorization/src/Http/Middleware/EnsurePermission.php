<?php

namespace Modules\Authorization\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Authorization\Exceptions\AuthorizationException;
use Modules\Authorization\Services\PermissionChecker;
use Modules\Authorization\Support\RoutePermissionResolver;
use Symfony\Component\HttpFoundation\Response;

/**
 * `permission:code` or `permission:code1,code2` (any-of).
 * Bare `permission` resolves codes from config/authorization.php route map.
 */
class EnsurePermission
{
    public function __construct(
        protected PermissionChecker $checker,
        protected RoutePermissionResolver $resolver,
    ) {}

    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        $user = $request->user();

        if (! $user) {
            throw AuthorizationException::forbidden('Unauthenticated.');
        }

        $codes = $abilities !== []
            ? $this->normalizeAbilities($abilities)
            : $this->resolver->codesFor($request);

        if ($codes === null) {
            throw AuthorizationException::forbidden(
                'This route is not mapped to a permission. Add it to config/authorization.php.',
            );
        }

        // Empty list = authenticated session routes (me, logout, own sessions).
        if ($codes === []) {
            return $next($request);
        }

        if (! $this->checker->allows($user, ...$codes)) {
            throw AuthorizationException::forbidden(
                'You do not have permission to perform this action.',
            );
        }

        return $next($request);
    }

    /**
     * @param  list<string>  $abilities
     * @return list<string>
     */
    protected function normalizeAbilities(array $abilities): array
    {
        $codes = [];
        foreach ($abilities as $ability) {
            foreach (explode(',', $ability) as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $codes[] = $part;
                }
            }
        }

        return $codes;
    }
}
