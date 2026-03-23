<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\Admin\Auth\LoginRequest;
use App\Http\Requests\Admin\Auth\ChangePasswordRequest;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $email = strtolower(trim((string) $request->validated('email')));
        $adminUser = AdminUser::query()->where('email', $email)->first();

        if (! $adminUser || ! Hash::check($request->validated('password'), $adminUser->password)) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials are incorrect.',
            ]);
        }

        $adminUser->forceFill([
            'last_login_at' => now(),
        ])->save();

        $accessToken = $adminUser->createToken('admin-access')->plainTextToken;

        return response()->json([
            'accessToken' => $accessToken,
            'tokenType' => 'Bearer',
            'user' => $this->serializeAdminUser($adminUser->fresh()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var AdminUser $adminUser */
        $adminUser = $request->user();
        $adminUser->tokens()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var AdminUser $adminUser */
        $adminUser = $request->user();

        return response()->json([
            'user' => $this->serializeAdminUser($adminUser),
        ]);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        /** @var AdminUser $adminUser */
        $adminUser = $request->user();

        if (! Hash::check($request->validated('currentPassword'), $adminUser->password)) {
            throw ValidationException::withMessages([
                'currentPassword' => 'The current password is incorrect.',
            ]);
        }

        $adminUser->forceFill([
            'password' => $request->validated('newPassword'),
        ])->save();

        $currentTokenId = $adminUser->currentAccessToken()?->id;

        if ($currentTokenId) {
            $adminUser->tokens()
                ->where('id', '!=', $currentTokenId)
                ->delete();
        }

        return response()->json([
            'message' => 'Password changed successfully.',
            'user' => $this->serializeAdminUser($adminUser->fresh()),
        ]);
    }

    private function serializeAdminUser(AdminUser $adminUser): array
    {
        return [
            'id' => $adminUser->id,
            'name' => $adminUser->name,
            'email' => $adminUser->email,
            'role' => $adminUser->role,
            'lastLoginAt' => optional($adminUser->last_login_at)->toIso8601String(),
        ];
    }
}
