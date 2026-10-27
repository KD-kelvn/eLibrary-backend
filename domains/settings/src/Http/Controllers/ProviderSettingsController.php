<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Settings\Http\Requests\UpdateProviderSettingsRequest;
use Modules\Settings\Services\ProviderSettingsService;

class ProviderSettingsController extends Controller
{
    public function __construct(
        private readonly ProviderSettingsService $providers,
    ) {}

    public function show(): JsonResponse
    {
        return response()->json([
            'data' => $this->providers->publicView(),
        ]);
    }

    public function update(UpdateProviderSettingsRequest $request): JsonResponse
    {
        $sms = $request->validated('sms') ?? [];

        return response()->json([
            'data' => $this->providers->updateSms(
                is_array($sms) ? $sms : [],
                $request->user()?->id,
            ),
        ]);
    }
}
