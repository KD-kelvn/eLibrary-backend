<?php

namespace Modules\Authentication\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Authentication\Http\Requests\IndexSessionRequest;
use Modules\Authentication\Http\Resources\SessionResource;
use Modules\Authentication\Models\PersonalAccessToken;
use Modules\Authentication\Models\User;
use Modules\Authentication\Services\SessionService;

class SessionController extends Controller
{
    public function __construct(private readonly SessionService $sessionService) {}

    /** Authenticated user's own sessions. */
    public function indexMine(Request $request): JsonResponse
    {
        try {
            $tokens = $this->sessionService->listForUser($request->user());

            return $this->successResponse(
                SessionResource::collection($tokens)->resolve(),
                'Sessions retrieved.',
            );
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    /** Admin: session KPI summary for cards. */
    public function summary(): JsonResponse
    {
        try {
            $this->authorize('viewAny', PersonalAccessToken::class);

            return $this->successResponse(
                $this->sessionService->summary(),
                'Session summary retrieved.',
            );
        } catch (AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    /** Admin: paginated sessions across users. */
    public function index(IndexSessionRequest $request): JsonResponse
    {
        try {
            $paginator = $this->sessionService->listAdmin(
                $request->validated(),
                (int) $request->input('per_page', 15),
            );

            return $this->successResponse([
                'items' => SessionResource::collection($paginator->items())->resolve(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                    'from' => $paginator->firstItem(),
                ],
            ], 'Sessions retrieved.');
        } catch (AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    /** Authenticated user: revoke one of their own sessions. */
    public function destroyMine(Request $request, PersonalAccessToken $personalAccessToken): JsonResponse
    {
        try {
            $user = $request->user();
            $current = $user->currentAccessToken();

            if (
                $personalAccessToken->tokenable_type !== $user->getMorphClass()
                || (int) $personalAccessToken->tokenable_id !== (int) $user->id
            ) {
                return $this->failResponse(null, 'Session not found.', 404);
            }

            if ($current && (int) $current->id === (int) $personalAccessToken->id) {
                return $this->failResponse(
                    null,
                    'Use logout to end your current session.',
                    422,
                );
            }

            $this->sessionService->revoke($personalAccessToken);

            return $this->successResponse(null, 'Session revoked.');
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    /** Authenticated user: revoke every session except the current one. */
    public function destroyOthers(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $current = $user->currentAccessToken();

            if (! $current instanceof PersonalAccessToken) {
                return $this->failResponse(null, 'Current session not found.', 422);
            }

            $count = $this->sessionService->revokeOthers($user, $current);

            return $this->successResponse(
                ['revokedCount' => $count],
                'Other sessions signed out.',
            );
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    /** Admin: revoke a single session. */
    public function destroy(Request $request, PersonalAccessToken $personalAccessToken): JsonResponse
    {
        try {
            $this->authorize('revoke', $personalAccessToken);
            $this->sessionService->revoke($personalAccessToken, $request->user());

            return $this->successResponse(null, 'Session revoked.');
        } catch (AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    /** Admin: revoke all sessions for a user. */
    public function destroyForUser(Request $request, User $user): JsonResponse
    {
        try {
            $this->authorize('revokeForUser', PersonalAccessToken::class);

            if ((int) $user->id === (int) $request->user()->id) {
                return $this->failResponse(
                    null,
                    'You cannot revoke all of your own sessions here. Use “Sign out other devices”.',
                    422,
                );
            }

            $count = $this->sessionService->revokeAllForUser($user, $request->user());

            return $this->successResponse(
                ['revokedCount' => $count],
                'All sessions for this user were revoked.',
            );
        } catch (AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }
}
