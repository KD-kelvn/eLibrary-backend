<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps a valid incoming X-Request-Id or creates a UUIDv7 so one id follows the
 * request through responses, Context (logs), and queued jobs.
 */
final class AssignRequestId
{
    public const HEADER = 'X-Request-Id';

    public function handle(Request $request, Closure $next): Response
    {
        $incoming = $request->headers->get(self::HEADER);
        $id = is_string($incoming) && Str::isUuid($incoming)
            ? strtolower($incoming)
            : (string) Str::uuid7();

        $request->attributes->set('request_id', $id);
        Context::add('request_id', $id);

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set(self::HEADER, $id);

        return $response;
    }
}
