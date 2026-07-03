<?php

namespace Modules\Authorization\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Authorization\Exceptions\AuthorizationException;
use Modules\Authorization\Http\Requests\IndexSystemModuleRequest;
use Modules\Authorization\Http\Requests\StoreSystemModuleRequest;
use Modules\Authorization\Http\Requests\UpdateSystemModuleRequest;
use Modules\Authorization\Http\Resources\SystemModuleResource;
use Modules\Authorization\Models\SystemModule;
use Modules\Authorization\Services\SystemModuleService;
use Modules\Authorization\Traits\HandlesApiResponses;

class SystemModuleController extends Controller
{
    use HandlesApiResponses;

    public function __construct(private readonly SystemModuleService $systemModuleService) {}

    public function index(IndexSystemModuleRequest $request): JsonResponse
    {
        try {
            $paginator = $this->systemModuleService->list(
                $request->validated(),
                (int) $request->input('per_page', 15),
            );

            return $this->paginatedResponse(
                $paginator,
                SystemModuleResource::class,
                'System modules retrieved.',
            );
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function show(string $system_module): JsonResponse
    {
        try {
            $this->authorize('view', SystemModule::query()->findOrFail($system_module));

            return $this->resourceResponse(
                SystemModuleResource::make($this->systemModuleService->show((int) $system_module)),
                'System module retrieved.',
            );
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function store(StoreSystemModuleRequest $request): JsonResponse
    {
        try {
            $module = $this->systemModuleService->store($request->validated());

            return $this->resourceResponse(
                SystemModuleResource::make($module),
                'System module created.',
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

    public function update(UpdateSystemModuleRequest $request, string $system_module): JsonResponse
    {
        try {
            $module = $this->systemModuleService->update((int) $system_module, $request->validated());

            return $this->resourceResponse(
                SystemModuleResource::make($module),
                'System module updated.',
            );
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function destroy(string $system_module): JsonResponse
    {
        try {
            $module = SystemModule::query()->findOrFail($system_module);
            $this->authorize('delete', $module);

            $this->systemModuleService->destroy((int) $system_module);

            return $this->successResponse(null, 'System module deleted.');
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }
}
