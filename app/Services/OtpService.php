<?php

namespace App\Services;

use App\Mail\PortalOtpMail;
use App\Models\PortalOtpChallenge;
use App\Models\PortalUser;
use Throwable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class OtpService
{
    private const MAX_VERIFICATION_ATTEMPTS = 5;

    public function issueChallenge(PortalUser $portalUser): PortalOtpChallenge
    {
        PortalOtpChallenge::query()
            ->where('email', $portalUser->email)
            ->whereNull('consumed_at')
            ->delete();

        $code = $this->generateCode();

        $challenge = PortalOtpChallenge::create([
            'portal_user_id' => $portalUser->id,
            'email' => $portalUser->email,
            'role' => $portalUser->role,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
        ]);

        try {
            Mail::to($portalUser->email)->send(new PortalOtpMail($portalUser, $code));
        } catch (Throwable $error) {
            if ($this->fixedCode() === null) {
                throw $error;
            }
        }

        return $challenge;
    }

    public function verifyChallenge(string $email, string $code): PortalUser
    {
        $challenge = PortalOtpChallenge::query()
            ->where('email', strtolower(trim($email)))
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $challenge || $challenge->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'code' => 'This OTP is invalid or has expired.',
            ]);
        }

        if ($challenge->attempts >= self::MAX_VERIFICATION_ATTEMPTS) {
            throw ValidationException::withMessages([
                'code' => 'This OTP has been locked due to too many invalid attempts.',
            ]);
        }

        $challenge->increment('attempts');

        if (! Hash::check($code, $challenge->code_hash)) {
            $challenge->refresh();

            if ($challenge->attempts >= self::MAX_VERIFICATION_ATTEMPTS) {
                $challenge->forceFill([
                    'consumed_at' => now(),
                ])->save();

                throw ValidationException::withMessages([
                    'code' => 'This OTP has been locked due to too many invalid attempts.',
                ]);
            }

            throw ValidationException::withMessages([
                'code' => 'The OTP code is incorrect.',
            ]);
        }

        $challenge->forceFill([
            'consumed_at' => now(),
        ])->save();

        return $challenge->portalUser ?: PortalUser::query()->where('email', $challenge->email)->firstOrFail();
    }

    private function generateCode(): string
    {
        $fixedCode = $this->fixedCode();
        if ($fixedCode !== null) {
            return $fixedCode;
        }

        return (string) random_int(100000, 999999);
    }

    private function fixedCode(): ?string
    {
        if (! app()->environment('testing')) {
            return null;
        }

        $fixedCode = trim((string) env('PORTAL_FIXED_OTP', ''));

        return $fixedCode !== '' ? $fixedCode : null;
    }
}
