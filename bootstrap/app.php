<?php

use App\Http\Middleware\AssignRequestId;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Authentication\Exceptions\AuthenticationException;
use Modules\Authorization\Exceptions\AuthorizationException;
use Modules\Authorization\Http\Middleware\EnsurePermission;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(AssignRequestId::class);
        $middleware->alias([
            'permission' => EnsurePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $payload = [
                    'success' => false,
                    'message' => $e->getMessage(),
                    'data' => null,
                ];

                if ($e->errorCode()) {
                    $payload['code'] = $e->errorCode();
                }

                return response()->json(
                    $payload,
                    $e->getCode() >= 400 ? $e->getCode() : 400,
                );
            }
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $payload = [
                    'success' => false,
                    'message' => $e->getMessage(),
                    'data' => null,
                ];

                if ($e->errorCode()) {
                    $payload['code'] = $e->errorCode();
                }

                return response()->json(
                    $payload,
                    $e->getCode() >= 400 ? $e->getCode() : 400,
                );
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'data' => null,
                    'errors' => $e->errors(),
                ], 422);
            }
        });
    })
    ->create();
