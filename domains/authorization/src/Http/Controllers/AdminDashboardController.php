<?php

namespace Modules\Authorization\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Authorization\Http\Resources\UserRoleResource;
use Modules\Authorization\Services\AdminDashboardService;

class AdminDashboardController extends Controller
{
    public function __construct(private readonly AdminDashboardService $service) {}

    public function __invoke(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasActiveRole('admin'), 403);

        $summary = $this->service->summary();
        $summary['recentRoleAssignments'] = UserRoleResource::collection(
            $summary['recentRoleAssignments'],
        );

        return $this->successResponse($summary, 'Admin dashboard retrieved.');
    }
}
