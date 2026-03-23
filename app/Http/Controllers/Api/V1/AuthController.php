<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\PortalUser;
use App\Services\OtpService;
use App\Services\TokenService;
use Illuminate\Http\JsonResponse;
use App\Support\PortalProfileDefaults;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Auth\RequestOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Requests\Auth\RegisterPortalUserRequest;

class AuthController extends Controller
{
    public function requestOtp(RequestOtpRequest $request, OtpService $otpService): JsonResponse
    {
        $portalUser = PortalUser::query()
            ->where('email', $request->validated('email'))
            ->first();
        $expiresAt = now()->addMinutes(10)->toIso8601String();

        if ($portalUser) {
            $challenge = $otpService->issueChallenge($portalUser);
            $expiresAt = $challenge->expires_at->toIso8601String();
        }

        return response()->json([
            'message' => 'If the email can receive portal access, a verification code will be sent shortly.',
            'email' => $request->validated('email'),
            'expiresAt' => $expiresAt,
            'devOtpCode' => $this->devOtpCode(),
        ], 202);
    }

    public function register(RegisterPortalUserRequest $request, OtpService $otpService): JsonResponse
    {
        $validated = $request->validated();
        $defaults = PortalProfileDefaults::forRole($validated['role']);

        $portalUser = PortalUser::create([
            'role' => $validated['role'],
            'portal_mode' => in_array($validated['role'], ['corporate', 'wholesale'], true)
                ? 'group'
                : 'individual',
            'group_type' => in_array($validated['role'], ['corporate', 'wholesale'], true)
                ? $validated['role']
                : null,
            'has_individual_access' => $validated['role'] === 'individual',
            'has_group_access' => in_array($validated['role'], ['corporate', 'wholesale'], true),
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'organization_name' => $validated['organizationName'] ?: null,
            'headline' => $defaults['headline'],
            'notes' => $defaults['notes'],
        ]);

        $challenge = $otpService->issueChallenge($portalUser);

        return response()->json([
            'message' => 'Portal account created. OTP sent to the account email.',
            'email' => $portalUser->email,
            'role' => $portalUser->role,
            'portalMode' => $portalUser->portal_mode,
            'groupType' => $portalUser->group_type,
            'hasIndividualAccess' => (bool) $portalUser->has_individual_access,
            'hasGroupAccess' => (bool) $portalUser->has_group_access,
            'expiresAt' => $challenge->expires_at->toIso8601String(),
            'devOtpCode' => $this->devOtpCode(),
        ], 201);
    }

    public function verifyOtp(
        VerifyOtpRequest $request,
        OtpService $otpService,
        TokenService $tokenService
    ): JsonResponse {
        $portalUser = $otpService->verifyChallenge(
            $request->validated('email'),
            $request->validated('code'),
        );

        if (! $portalUser->verified_at) {
            $portalUser->forceFill([
                'verified_at' => now(),
            ])->save();
        }

        $tokens = $tokenService->issueTokenPair($portalUser);

        return response()->json([
            ...$tokens,
            'user' => $this->serializePortalUser($portalUser->fresh()),
        ]);
    }

    public function refresh(RefreshTokenRequest $request, TokenService $tokenService): JsonResponse
    {
        $tokens = $tokenService->refresh($request->validated('refreshToken'));

        return response()->json($tokens);
    }

    public function logout(Request $request, TokenService $tokenService): JsonResponse
    {
        /** @var PortalUser $portalUser */
        $portalUser = $request->user();
        $tokenService->revokeForUser($portalUser);

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var PortalUser $portalUser */
        $portalUser = $request->user();

        return response()->json([
            'user' => $this->serializePortalUser($portalUser),
        ]);
    }

    private function serializePortalUser(PortalUser $portalUser): array
    {
        return [
            'id' => $portalUser->id,
            'role' => $portalUser->role,
            'portalMode' => $portalUser->portal_mode,
            'groupType' => $portalUser->group_type,
            'hasIndividualAccess' => (bool) $portalUser->has_individual_access,
            'hasGroupAccess' => (bool) $portalUser->has_group_access,
            'name' => $portalUser->name,
            'email' => $portalUser->email,
            'phone' => $portalUser->phone,
            'organizationName' => $portalUser->organization_name,
            'headline' => $portalUser->headline,
            'notes' => $portalUser->notes ?: [],
            'verifiedAt' => optional($portalUser->verified_at)->toIso8601String(),
            'supportsTickets' => $portalUser->supportsTickets(),
        ];
    }

    private function devOtpCode(): ?string
    {
        if (! app()->environment('testing')) {
            return null;
        }

        $fixedCode = trim((string) env('PORTAL_FIXED_OTP', ''));

        return $fixedCode !== '' ? $fixedCode : null;
    }
}
