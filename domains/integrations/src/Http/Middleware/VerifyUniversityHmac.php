<?php

namespace Modules\Integrations\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Integrations\Exceptions\IntegrationsException;
use Modules\Integrations\Services\UniversityHmacVerifier;
use Symfony\Component\HttpFoundation\Response;

class VerifyUniversityHmac
{
    public function __construct(private readonly UniversityHmacVerifier $verifier) {}

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $this->verifier->assertValid(
                $request->header('X-Signature'),
                $request->header('X-Timestamp'),
                $request->getContent(),
            );
        } catch (IntegrationsException $e) {
            return response()->json([
                'status' => 'fail',
                'message' => $e->getMessage(),
                'code' => 'HMAC_INVALID',
                'data' => null,
                'errors' => null,
            ], $e->getCode() ?: 401);
        }

        return $next($request);
    }
}
