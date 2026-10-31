<?php

namespace Modules\Integrations\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Integrations\Exceptions\IntegrationsException;
use Modules\Integrations\Http\Requests\PushUniversityStudentsRequest;
use Modules\Integrations\Services\SyncPushService;

class UniversityStudentPushController extends Controller
{
    public function __construct(private readonly SyncPushService $push) {}

    public function store(PushUniversityStudentsRequest $request): JsonResponse
    {
        $key = $request->header('Idempotency-Key');
        if (! filled($key)) {
            return $this->failResponse(null, 'Idempotency-Key header is required.', 422, errorCode: 'IDEMPOTENCY_REQUIRED');
        }

        try {
            $result = $this->push->acceptPush(
                $request->validated('students'),
                (string) $key,
            );

            return $this->successResponse($result, 'Sync run accepted.', 202);
        } catch (IntegrationsException $e) {
            return $this->failResponse(null, $e->getMessage(), $e->getCode() ?: 422);
        }
    }
}
