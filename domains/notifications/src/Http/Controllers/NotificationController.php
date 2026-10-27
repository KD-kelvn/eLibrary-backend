<?php

namespace Modules\Notifications\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Notifications\Http\Resources\NotificationResource;
use Modules\Notifications\Services\NotificationService;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->notifications->listForUser(
            $request->user(),
            (int) $request->input('per_page', 20),
        );

        return $this->successResponse([
            'items' => NotificationResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'unread_count' => $this->notifications->unreadCount($request->user()),
            ],
        ], 'Notifications retrieved.');
    }

    public function markRead(Request $request, string $notification): JsonResponse
    {
        $model = $this->notifications->markRead($request->user(), $notification);

        return $this->successResponse(
            NotificationResource::make($model),
            'Notification marked as read.',
        );
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $count = $this->notifications->markAllRead($request->user());

        return $this->successResponse(
            ['updated' => $count],
            'All notifications marked as read.',
        );
    }
}
