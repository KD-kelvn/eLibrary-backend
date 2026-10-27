<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Modules\Academic\Exceptions\AcademicException;
use Modules\Academic\Http\Requests\IndexProgrammeRequest;
use Modules\Academic\Http\Requests\StoreProgrammeRequest;
use Modules\Academic\Http\Requests\UpdateProgrammeRequest;
use Modules\Academic\Http\Resources\ProgrammeResource;
use Modules\Academic\Services\ProgrammeService;
use Modules\Academic\Traits\HandlesApiResponses;

class ProgrammeController extends Controller
{
    use HandlesApiResponses;

    public function __construct(private readonly ProgrammeService $programmeService) {}

    public function index(IndexProgrammeRequest $request): JsonResponse
    {
        try {
            $paginator = $this->programmeService->list(
                $request->validated(),
                (int) $request->input('per_page', 15),
            );

            return $this->paginatedResponse($paginator, ProgrammeResource::class, 'Programmes retrieved.');
        } catch (AcademicException $exception) {
            return $this->handleAcademicException($exception);
        } catch (AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function show(string $programme): JsonResponse
    {
        try {
            return $this->resourceResponse(
                ProgrammeResource::make($this->programmeService->show((int) $programme)),
                'Programme retrieved.',
            );
        } catch (AcademicException $exception) {
            return $this->handleAcademicException($exception);
        } catch (AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function store(StoreProgrammeRequest $request): JsonResponse
    {
        try {
            $programme = $this->programmeService->store($request->validated());

            return $this->resourceResponse(
                ProgrammeResource::make($programme),
                'Programme created.',
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

    public function update(UpdateProgrammeRequest $request, string $programme): JsonResponse
    {
        try {
            $updated = $this->programmeService->update((int) $programme, $request->validated());

            return $this->resourceResponse(
                ProgrammeResource::make($updated),
                'Programme updated.',
            );
        } catch (AcademicException $exception) {
            return $this->handleAcademicException($exception);
        } catch (AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function destroy(string $programme): JsonResponse
    {
        try {
            $this->programmeService->destroy((int) $programme);

            return $this->successResponse(null, 'Programme deleted.');
        } catch (AcademicException $exception) {
            return $this->handleAcademicException($exception);
        } catch (AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }
}
