<?php

namespace App\Http\Controllers\Api\V1\Seller;

use App\Models\Seller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'pin' => 'required|string|min:4|max:6',
        ]);

        $phone = trim((string) $request->input('phone'));
        $pin = trim((string) $request->input('pin'));

        $seller = Seller::query()->where('phone', $phone)->first();

        if (! $seller || ! $seller->verifyPin($pin)) {
            throw ValidationException::withMessages([
                'phone' => 'The provided credentials are incorrect.',
            ]);
        }

        if ($seller->status !== 'active') {
            throw ValidationException::withMessages([
                'phone' => 'Your account has been suspended. Please contact support.',
            ]);
        }

        $seller->forceFill([
            'last_login_at' => now(),
        ])->save();

        $accessToken = $seller->createToken('seller-access')->plainTextToken;

        return response()->json([
            'accessToken' => $accessToken,
            'refreshToken' => null,
            'expiresIn' => 3600,
            'seller' => $this->serializeSeller($seller),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var Seller $seller */
        $seller = $request->user();
        $seller->tokens()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var Seller $seller */
        $seller = $request->user();

        return response()->json([
            'seller' => $this->serializeSeller($seller),
        ]);
    }

    private function serializeSeller(Seller $seller): array
    {
        return [
            'id' => $seller->id,
            'name' => $seller->name,
            'code' => $seller->code,
            'phone' => $seller->phone,
            'status' => $seller->status,
            'lastLoginAt' => optional($seller->last_login_at)->toIso8601String(),
        ];
    }
}
