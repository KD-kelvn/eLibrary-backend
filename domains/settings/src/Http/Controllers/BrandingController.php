<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Modules\Settings\Http\Requests\UpdateBrandingRequest;
use Modules\Settings\Http\Resources\BrandingResource;
use Modules\Settings\Services\BrandingService;

class BrandingController extends Controller
{
    public function __construct(private readonly BrandingService $brandingService) {}

    public function show(): JsonResponse
    {
        try {
            $branding = $this->brandingService->current();

            return $this->successResponse(
                BrandingResource::make($branding),
                'Branding retrieved.',
            );
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function update(UpdateBrandingRequest $request): JsonResponse
    {
        try {
            $branding = $this->brandingService->update($request->validated());

            return $this->successResponse(
                BrandingResource::make($branding),
                'Branding updated.',
            );
        } catch (AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }
}
