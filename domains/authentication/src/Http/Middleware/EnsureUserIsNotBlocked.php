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
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->is_blocked) {
            $user->tokens()->delete();

            return $this->denied('This account has been blocked from using the system.');
        }

        if (! $user->is_active) {
            $user->tokens()->delete();

            return $this->denied('This account is inactive.');
        }

        if ($user->isLocked()) {
            return $this->denied('This account is temporarily locked. Try again later.');
        }

        return $next($request);
    }

    protected function denied(string $message): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
            'data' => null,
        ], 403);
    }
}
