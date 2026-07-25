<?php

namespace Modules\Authentication\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsNotBlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->is_blocked) {
            $request->user()->tokens()->delete();

            return new JsonResponse([
                'success' => false,
                'message' => 'This account has been blocked from using the system.',
                'data' => null,
            ], 403);
        }

        return $next($request);
    }
}
