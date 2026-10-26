<?php

namespace Modules\Notifications\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Notifications\Http\Requests\UpdateNotificationPreferencesRequest;
use Modules\Notifications\Services\NotificationService;

class NotificationPreferenceController extends Controller
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function show(Request $request): JsonResponse
    {
        return $this->successResponse([
            'items' => $this->notifications->preferencesFor($request->user()),
        ], 'Notification preferences retrieved.');
    }

    public function update(UpdateNotificationPreferencesRequest $request): JsonResponse
    {
        $items = $this->notifications->syncPreferences(
            $request->user(),
            $request->validated('preferences'),
        );

        return $this->successResponse(['items' => $items], 'Notification preferences updated.');
    }
}
