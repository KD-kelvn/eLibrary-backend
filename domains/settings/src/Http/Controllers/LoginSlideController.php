<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Modules\Settings\Http\Requests\StoreLoginSlideRequest;
use Modules\Settings\Http\Requests\UpdateLoginSlideRequest;
use Modules\Settings\Http\Resources\LoginSlideResource;
use Modules\Settings\Models\LoginSlide;
use Modules\Settings\Services\LoginSlideService;

class LoginSlideController extends Controller
{
    public function __construct(private readonly LoginSlideService $loginSlideService) {}

    /** Public active slides (Laravel-cached). */
    public function index(): JsonResponse
    {
        try {
            return $this->successResponse(
                $this->loginSlideService->activeCached(),
                'Login slides retrieved.',
            );
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    /** Admin list of all slides. */
    public function manage(): JsonResponse
    {
        try {
            $this->authorize('viewAny', LoginSlide::class);

                        return $this->successResponse(
                LoginSlideResource::collection($this->loginSlideService->all())->resolve(),
                'Login slides retrieved.',
            );
        } catch (AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function store(StoreLoginSlideRequest $request): JsonResponse
    {
        try {
            $slide = $this->loginSlideService->create($request->validated());

            return $this->successResponse(
                LoginSlideResource::make($slide)->resolve(),
                'Login slide created.',
                201,
            );
        } catch (AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function update(UpdateLoginSlideRequest $request, LoginSlide $loginSlide): JsonResponse
    {
        try {
            $slide = $this->loginSlideService->update($loginSlide, $request->validated());

            return $this->successResponse(
                LoginSlideResource::make($slide)->resolve(),
                'Login slide updated.',
            );
        } catch (AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function destroy(LoginSlide $loginSlide): JsonResponse
    {
        try {
            $this->authorize('delete', $loginSlide);
            $this->loginSlideService->delete($loginSlide);

            return $this->successResponse(null, 'Login slide deleted.');
        } catch (AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }
}
