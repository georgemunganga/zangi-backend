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
        $refreshTokenLookup = Str::random(24);
        $refreshTokenSecret = Str::random(80);
        $plainRefreshToken = $refreshTokenLookup.'.'.$refreshTokenSecret;

        RefreshToken::create([
            'portal_user_id' => $portalUser->id,
            'token_lookup' => hash('sha256', $refreshTokenLookup),
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
        $lookup = Str::before($plainRefreshToken, '.');

        if ($lookup === '' || ! str_contains($plainRefreshToken, '.')) {
            throw ValidationException::withMessages([
                'refreshToken' => 'The refresh token is invalid or has expired.',
            ]);
        }

        $refreshToken = RefreshToken::query()
            ->where('token_lookup', hash('sha256', $lookup))
            ->whereNull('revoked_at')
            ->first();

        if (! $refreshToken || $refreshToken->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'refreshToken' => 'The refresh token is invalid or has expired.',
            ]);
        }

        if (! Hash::check($plainRefreshToken, $refreshToken->token_hash)) {
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
