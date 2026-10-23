<?php

namespace Modules\Authorization\Support;

use Illuminate\Http\Request;

/**
 * Resolves permission codes for the current request from config/authorization.php.
 *
 * @return list<string>|null null = unmapped (coverage test fails); [] = auth-only OK
 */
class RoutePermissionResolver
{
    /**
     * @return list<string>|null
     */
    public function codesFor(Request $request): ?array
    {
        $method = strtoupper($request->method());
        if ($method === 'HEAD') {
            $method = 'GET';
        }

        $path = ltrim($request->path(), '/');

        $map = config('authorization.route_permissions', []);

        foreach ($map as $pattern => $codes) {
            [$patternMethod, $patternPath] = array_pad(explode(' ', $pattern, 2), 2, null);
            if ($patternPath === null) {
                continue;
            }

            $patternMethod = strtoupper($patternMethod);
            if ($patternMethod !== $method && $patternMethod !== 'ANY') {
                continue;
            }

            if ($this->pathMatches($path, ltrim($patternPath, '/'))) {
                return array_values((array) $codes);
            }
        }

        return null;
    }

    protected function pathMatches(string $path, string $pattern): bool
    {
        if ($pattern === $path) {
            return true;
        }

        $parts = preg_split('/(\{[^}]+\})/', $pattern, -1, PREG_SPLIT_DELIM_CAPTURE);
        $regex = '';

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            if (preg_match('/^\{[^}]+\}$/', $part) === 1) {
                $regex .= '[^/]+';
            } else {
                $regex .= preg_quote($part, '#');
            }
        }

        return (bool) preg_match('#^'.$regex.'$#', $path);
    }
}
