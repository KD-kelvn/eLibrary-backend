<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Modules\Settings\Http\Requests\CancelPendingLibrarySettingRequest;
use Modules\Settings\Http\Requests\ResetLibrarySettingsRequest;
use Modules\Settings\Http\Requests\UpdateLibrarySettingsRequest;
use Modules\Settings\Services\SettingsService;
use Modules\Settings\Support\LibrarySettingsAuthorizer;
use Modules\Settings\Support\SettingsRegistry;

class LibrarySettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly LibrarySettingsAuthorizer $authorizer,
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $this->assertCanView($request);

            return $this->successResponse(
                [
                    'settings' => $this->settings->catalog($request->query('group')),
                    'groups' => app(SettingsRegistry::class)->groups(),
                ],
                'Library settings retrieved.',
            );
        } catch (AuthorizationException $e) {
            return $this->failResponse(null, $e->getMessage(), 403);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500, $e);
        }
    }

    public function publicIndex(): JsonResponse
    {
        try {
            return $this->successResponse(
                ['settings' => $this->settings->publicCatalog()],
                'Public library settings retrieved.',
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500, $e);
        }
    }

    public function update(UpdateLibrarySettingsRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $rows = $this->settings->changeBatch(
                $data['changes'],
                $data['reason'],
                $data['effective_from'] ?? null,
                $request->user()?->id,
            );

            return $this->successResponse(
                [
                    'changed' => collect($rows)->map(fn ($row) => [
                        'id' => $row->id,
                        'key' => $row->key,
                        'scope' => $row->scope,
                        'value' => $row->decodedValue(),
                        'effectiveFrom' => optional($row->effective_from)?->toIso8601String(),
                    ])->all(),
                ],
                'Library settings updated.',
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500, $e);
        }
    }

    public function reset(ResetLibrarySettingsRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $rows = $this->settings->resetRequest(
                reason: $data['reason'],
                keys: $data['keys'] ?? null,
                group: $data['group'] ?? null,
                all: filter_var($data['all'] ?? false, FILTER_VALIDATE_BOOLEAN),
                roleCode: $data['role'] ?? null,
                effectiveFrom: $data['effective_from'] ?? null,
                createdBy: $request->user()?->id,
            );

            return $this->successResponse(
                [
                    'reset' => collect($rows)->map(fn ($row) => [
                        'id' => $row->id,
                        'key' => $row->key,
                        'scope' => $row->scope,
                        'value' => $row->decodedValue(),
                    ])->all(),
                ],
                'Library settings reset.',
            );
        } catch (InvalidArgumentException $e) {
            return $this->failResponse(null, $e->getMessage(), 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500, $e);
        }
    }

    public function cancelPending(CancelPendingLibrarySettingRequest $request, int $id): JsonResponse
    {
        try {
            $row = $this->settings->cancel($id, $request->validated('reason'));

            return $this->successResponse(
                [
                    'id' => $row->id,
                    'key' => $row->key,
                    'scope' => $row->scope,
                    'cancelledAt' => optional($row->cancelled_at)?->toIso8601String(),
                ],
                'Pending setting change cancelled.',
            );
        } catch (InvalidArgumentException $e) {
            return $this->failResponse(null, $e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500, $e);
        }
    }

    public function history(Request $request): JsonResponse
    {
        try {
            $this->assertCanView($request);

            return $this->successResponse(
                [
                    'history' => $this->settings->history(
                        $request->query('key'),
                        $request->query('scope'),
                        $request->query('from'),
                        $request->query('to'),
                    ),
                ],
                'Library settings history retrieved.',
            );
        } catch (AuthorizationException $e) {
            return $this->failResponse(null, $e->getMessage(), 403);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500, $e);
        }
    }

    protected function assertCanView(Request $request): void
    {
        $user = $request->user();
        if (! $user || ! $this->authorizer->canView($user)) {
            throw new AuthorizationException('You are not allowed to view library settings.');
        }
    }
}
