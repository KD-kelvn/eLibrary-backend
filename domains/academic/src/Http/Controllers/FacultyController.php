<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Modules\Academic\Exceptions\AcademicException;
use Modules\Academic\Http\Requests\IndexFacultyRequest;
use Modules\Academic\Http\Requests\StoreFacultyRequest;
use Modules\Academic\Http\Requests\UpdateFacultyRequest;
use Modules\Academic\Http\Resources\FacultyResource;
use Modules\Academic\Services\FacultyService;
use Modules\Academic\Traits\HandlesApiResponses;

class FacultyController extends Controller
{
    use HandlesApiResponses;

    public function __construct(private readonly FacultyService $facultyService) {}

    public function index(IndexFacultyRequest $request): JsonResponse
    {
        try {
            $paginator = $this->facultyService->list(
                $request->validated(),
                (int) $request->input('per_page', 15),
            );

            return $this->paginatedResponse($paginator, FacultyResource::class, 'Faculties retrieved.');
        } catch (AcademicException $exception) {
            return $this->handleAcademicException($exception);
        } catch (AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function show(string $faculty): JsonResponse
    {
        try {
            return $this->resourceResponse(
                FacultyResource::make($this->facultyService->show((int) $faculty)),
                'Faculty retrieved.',
            );
        } catch (AcademicException $exception) {
            return $this->handleAcademicException($exception);
        } catch (AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function store(StoreFacultyRequest $request): JsonResponse
    {
        try {
            $faculty = $this->facultyService->store($request->validated());

            return $this->resourceResponse(
                FacultyResource::make($faculty),
                'Faculty created.',
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

    public function update(UpdateFacultyRequest $request, string $faculty): JsonResponse
    {
        try {
            $updated = $this->facultyService->update((int) $faculty, $request->validated());

            return $this->resourceResponse(
                FacultyResource::make($updated),
                'Faculty updated.',
            );
        } catch (AcademicException $exception) {
            return $this->handleAcademicException($exception);
        } catch (AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function destroy(string $faculty): JsonResponse
    {
        try {
            $this->facultyService->destroy((int) $faculty);

            return $this->successResponse(null, 'Faculty deleted.');
        } catch (AcademicException $exception) {
            return $this->handleAcademicException($exception);
        } catch (AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }
}
