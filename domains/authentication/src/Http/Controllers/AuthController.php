<?php

namespace Modules\Authentication\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Authentication\Enums\OneTimeTokenPurpose;
use Modules\Authentication\Exceptions\AuthenticationException;
use Modules\Authentication\Http\Controllers\Concerns\RespondsWithJson;
use Modules\Authentication\Http\Requests\LoginRequest;
use Modules\Authentication\Http\Requests\RegisterRequest;
use Modules\Authentication\Http\Requests\RequestOtpRequest;
use Modules\Authentication\Http\Requests\VerifyOtpRequest;
use Modules\Authentication\Http\Resources\AuthResource;
use Modules\Authentication\Http\Resources\AuthenticatedUserResource;
use Modules\Authentication\Http\Resources\OtpSentResource;
use Modules\Authentication\Services\OneTimeTokenLoginService;
use Modules\Authentication\Services\PasswordLoginService;
use Modules\Authentication\Services\RegistrationService;

class AuthController extends Controller
{
    use RespondsWithJson;

    public function __construct(
        private readonly RegistrationService $registration,
        private readonly PasswordLoginService $passwordLogin,
        private readonly OneTimeTokenLoginService $otpLogin,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->registration->register($request->validated());

            return $this->successResponse(AuthResource::make($result), 'Account created.', 201);
        } catch (AuthenticationException $e) {
            return $this->failResponse(null, $e->getMessage(), $e->getCode() ?: 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->passwordLogin->login($request->validated());

            return $this->successResponse(AuthResource::make($result), 'Signed in successfully.');
        } catch (AuthenticationException $e) {
            return $this->failResponse(null, $e->getMessage(), $e->getCode() ?: 401);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function requestOtp(RequestOtpRequest $request): JsonResponse
    {
        try {
            $payload = $request->validated();
            $payload['ip_address'] = $request->ip();
            $payload['user_agent'] = $request->userAgent();

            $result = $this->otpLogin->requestToken($payload);

            return $this->successResponse(OtpSentResource::make($result), 'One-time code sent.');
        } catch (AuthenticationException $e) {
            return $this->failResponse(null, $e->getMessage(), $e->getCode() ?: 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        try {
            $payload = $request->validated();
            $payload['purpose'] = $payload['purpose'] ?? OneTimeTokenPurpose::Login->value;

            $result = $this->otpLogin->login($payload);

            return $this->successResponse(AuthResource::make($result), 'Signed in successfully.');
        } catch (AuthenticationException $e) {
            return $this->failResponse(null, $e->getMessage(), $e->getCode() ?: 401);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function me(Request $request): JsonResponse
    {
        try {
            return $this->success(
                AuthenticatedUserResource::make($request->user()->load('profile')),
                '',
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()?->delete();

            return $this->successResponse(null, 'Signed out successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
