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
        $data = $request->validated();
        $userId = $request->user()?->id;

        if (isset($data['sms']) && is_array($data['sms'])) {
            $this->providers->updateSms($data['sms'], $userId);
        }

        if (isset($data['university']) && is_array($data['university'])) {
            $this->providers->updateUniversity($data['university'], $userId);
        }

        return response()->json([
            'data' => $this->providers->publicView(),
        ]);
    }
}
