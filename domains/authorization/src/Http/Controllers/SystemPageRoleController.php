<?php

namespace Modules\Authorization\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Authorization\Exceptions\AuthorizationException;
use Modules\Authorization\Http\Requests\StoreSystemPageRoleRequest;
use Modules\Authorization\Http\Resources\SystemPageRoleResource;
use Modules\Authorization\Models\SystemPageRole;
use Modules\Authorization\Services\SystemPageRoleService;
use Modules\Authorization\Traits\HandlesApiResponses;

class SystemPageRoleController extends Controller
{
    use HandlesApiResponses;

    public function __construct(
        private readonly SystemPageRoleService $systemPageRoleService,
    ) {}

    public function store(StoreSystemPageRoleRequest $request): JsonResponse
    {
        try {
            $assignment = $this->systemPageRoleService->store($request->validated());

            return $this->resourceResponse(
                SystemPageRoleResource::make($assignment->load([
                    'systemPage',
                    'role',
                ])),
                'System page role assigned.',
                201,
            );
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function destroy(string $system_page_role): JsonResponse
    {
        try {
            $assignment = SystemPageRole::query()->findOrFail($system_page_role);
            $this->authorize('delete', $assignment);

            $this->systemPageRoleService->destroy((int) $system_page_role);

            return $this->successResponse(null, 'System page role assignment deleted.');
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }
}
