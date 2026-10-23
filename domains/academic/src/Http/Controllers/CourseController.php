<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Modules\Academic\Exceptions\AcademicException;
use Modules\Academic\Http\Requests\IndexCourseRequest;
use Modules\Academic\Http\Requests\StoreCourseRequest;
use Modules\Academic\Http\Requests\UpdateCourseRequest;
use Modules\Academic\Http\Resources\CourseResource;
use Modules\Academic\Services\CourseService;
use Modules\Academic\Traits\HandlesApiResponses;

class CourseController extends Controller
{
    use HandlesApiResponses;

    public function __construct(private readonly CourseService $courseService) {}

    public function index(IndexCourseRequest $request): JsonResponse
    {
        try {
            $paginator = $this->courseService->list(
                $request->validated(),
                (int) $request->input('per_page', 15),
            );

            return $this->paginatedResponse($paginator, CourseResource::class, 'Courses retrieved.');
        } catch (AcademicException $exception) {
            return $this->handleAcademicException($exception);
        } catch (AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function show(string $course): JsonResponse
    {
        try {
            return $this->resourceResponse(
                CourseResource::make($this->courseService->show((int) $course)),
                'Course retrieved.',
            );
        } catch (AcademicException $exception) {
            return $this->handleAcademicException($exception);
        } catch (AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function store(StoreCourseRequest $request): JsonResponse
    {
        try {
            $course = $this->courseService->store($request->validated());

            return $this->resourceResponse(
                CourseResource::make($course),
                'Course created.',
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

    public function update(UpdateCourseRequest $request, string $course): JsonResponse
    {
        try {
            $updated = $this->courseService->update((int) $course, $request->validated());

            return $this->resourceResponse(
                CourseResource::make($updated),
                'Course updated.',
            );
        } catch (AcademicException $exception) {
            return $this->handleAcademicException($exception);
        } catch (AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function destroy(string $course): JsonResponse
    {
        try {
            $this->courseService->destroy((int) $course);

            return $this->successResponse(null, 'Course deleted.');
        } catch (AcademicException $exception) {
            return $this->handleAcademicException($exception);
        } catch (AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }
}
