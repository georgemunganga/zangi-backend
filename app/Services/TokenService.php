<?php

namespace App\Services;

use App\Models\PortalUser;
use App\Models\RefreshToken;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TokenService
{
    public function issueTokenPair(PortalUser $portalUser): array
    {
        $accessToken = $portalUser->createToken('portal-access')->plainTextToken;
        $plainRefreshToken = Str::random(80);

        RefreshToken::create([
            'portal_user_id' => $portalUser->id,
            'token_hash' => Hash::make($plainRefreshToken),
            'expires_at' => now()->addDays(30),
        ]);

        return [
            'accessToken' => $accessToken,
            'refreshToken' => $plainRefreshToken,
            'tokenType' => 'Bearer',
            'expiresIn' => 3600,
        ];
    }

    public function refresh(string $plainRefreshToken): array
    {
        $refreshToken = RefreshToken::query()
            ->whereNull('revoked_at')
            ->get()
            ->first(fn (RefreshToken $token): bool => Hash::check($plainRefreshToken, $token->token_hash));

        if (! $refreshToken || $refreshToken->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'refreshToken' => 'The refresh token is invalid or has expired.',
            ]);
        }

        $refreshToken->forceFill([
            'revoked_at' => now(),
        ])->save();

        return $this->issueTokenPair($refreshToken->portalUser);
    }

    public function revokeForUser(PortalUser $portalUser): void
    {
        $portalUser->tokens()->delete();

        RefreshToken::query()
            ->where('portal_user_id', $portalUser->id)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
            ]);
    }
}
