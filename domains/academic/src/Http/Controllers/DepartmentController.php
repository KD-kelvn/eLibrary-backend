<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Modules\Academic\Exceptions\AcademicException;
use Modules\Academic\Http\Requests\IndexDepartmentRequest;
use Modules\Academic\Http\Requests\StoreDepartmentRequest;
use Modules\Academic\Http\Requests\UpdateDepartmentRequest;
use Modules\Academic\Http\Resources\DepartmentResource;
use Modules\Academic\Services\DepartmentService;
use Modules\Academic\Traits\HandlesApiResponses;

class DepartmentController extends Controller
{
    use HandlesApiResponses;

    public function __construct(private readonly DepartmentService $departmentService) {}

    public function index(IndexDepartmentRequest $request): JsonResponse
    {
        try {
            $paginator = $this->departmentService->list(
                $request->validated(),
                (int) $request->input('per_page', 15),
            );

            return $this->paginatedResponse($paginator, DepartmentResource::class, 'Departments retrieved.');
        } catch (AcademicException $exception) {
            return $this->handleAcademicException($exception);
        } catch (AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function show(string $department): JsonResponse
    {
        try {
            return $this->resourceResponse(
                DepartmentResource::make($this->departmentService->show((int) $department)),
                'Department retrieved.',
            );
        } catch (AcademicException $exception) {
            return $this->handleAcademicException($exception);
        } catch (AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        try {
            $department = $this->departmentService->store($request->validated());

            return $this->resourceResponse(
                DepartmentResource::make($department),
                'Department created.',
                201,
            );
        } catch (AcademicException $exception) {
            return $this->handleAcademicException($exception);
        } catch (AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function update(UpdateDepartmentRequest $request, string $department): JsonResponse
    {
        try {
            $updated = $this->departmentService->update((int) $department, $request->validated());

            return $this->resourceResponse(
                DepartmentResource::make($updated),
                'Department updated.',
            );
        } catch (AcademicException $exception) {
            return $this->handleAcademicException($exception);
        } catch (AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function destroy(string $department): JsonResponse
    {
        try {
            $this->departmentService->destroy((int) $department);

            return $this->successResponse(null, 'Department deleted.');
        } catch (AcademicException $exception) {
            return $this->handleAcademicException($exception);
        } catch (AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }
}
