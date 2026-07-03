<?php

namespace Modules\Authorization\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Authorization\Exceptions\AuthorizationException;
use Modules\Authorization\Http\Requests\IndexSystemPageRequest;
use Modules\Authorization\Http\Requests\StoreSystemPageRequest;
use Modules\Authorization\Http\Requests\UpdateSystemPageRequest;
use Modules\Authorization\Http\Resources\SystemPageResource;
use Modules\Authorization\Models\SystemPage;
use Modules\Authorization\Services\SystemPageService;
use Modules\Authorization\Traits\HandlesApiResponses;

class SystemPageController extends Controller
{
    use HandlesApiResponses;

    public function __construct(private readonly SystemPageService $systemPageService) {}

    public function index(IndexSystemPageRequest $request): JsonResponse
    {
        try {
            $paginator = $this->systemPageService->list(
                $request->validated(),
                (int) $request->input('per_page', 15),
            );

            return $this->paginatedResponse(
                $paginator,
                SystemPageResource::class,
                'System pages retrieved.',
            );
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function show(string $system_page): JsonResponse
    {
        try {
            $this->authorize('view', SystemPage::query()->findOrFail($system_page));

            return $this->resourceResponse(
                SystemPageResource::make($this->systemPageService->show((int) $system_page)),
                'System page retrieved.',
            );
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function store(StoreSystemPageRequest $request): JsonResponse
    {
        try {
            $page = $this->systemPageService->store($request->validated());

            return $this->resourceResponse(
                SystemPageResource::make($page),
                'System page created.',
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

    public function update(UpdateSystemPageRequest $request, string $system_page): JsonResponse
    {
        try {
            $page = $this->systemPageService->update((int) $system_page, $request->validated());

            return $this->resourceResponse(
                SystemPageResource::make($page),
                'System page updated.',
            );
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function destroy(string $system_page): JsonResponse
    {
        try {
            $page = SystemPage::query()->findOrFail($system_page);
            $this->authorize('delete', $page);

            $this->systemPageService->destroy((int) $system_page);

            return $this->successResponse(null, 'System page deleted.');
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }
}
