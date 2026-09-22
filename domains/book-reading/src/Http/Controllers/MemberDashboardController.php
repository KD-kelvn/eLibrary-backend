<?php

namespace Modules\BookReading\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\BookReading\Services\MemberDashboardService;

class MemberDashboardController extends Controller
{
    public function __construct(private readonly MemberDashboardService $dashboard) {}

    public function show(): JsonResponse
    {
        try {
            $user = request()->user();
            if (! $user) {
                return $this->failResponse(null, 'Unauthenticated.', 401);
            }

            $payload = $this->dashboard->summary((int) $user->id);

            return $this->successResponse($payload, 'Member dashboard retrieved.');
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }
}
