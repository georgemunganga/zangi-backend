<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', fn (Request $request) => $this->limitPerIp('api', $request, 120, 1));
        RateLimiter::for('portal-register', fn (Request $request) => [
            $this->limitPerIp('portal-register', $request, 10, 15),
            $this->limitByHashedInput('portal-register-email', $request, 'email', 5, 15),
        ]);
        RateLimiter::for('portal-otp-request', fn (Request $request) => [
            $this->limitPerIp('portal-otp-request', $request, 10, 15),
            $this->limitByHashedInput('portal-otp-request-email', $request, 'email', 8, 15),
        ]);
        RateLimiter::for('portal-otp-verify', fn (Request $request) => [
            $this->limitPerIp('portal-otp-verify', $request, 20, 10),
            $this->limitByHashedInput('portal-otp-verify-email', $request, 'email', 10, 10),
        ]);
        RateLimiter::for('portal-token-refresh', fn (Request $request) => [
            $this->limitPerIp('portal-token-refresh', $request, 20, 10),
            $this->limitByHashedInput('portal-token-refresh-token', $request, 'refreshToken', 10, 10),
        ]);
        RateLimiter::for('public-checkout', fn (Request $request) => [
            $this->limitPerIp('public-checkout', $request, 12, 10),
            $this->limitByHashedInput('public-checkout-email', $request, 'email', 6, 10),
        ]);
        RateLimiter::for('payment-intents', fn (Request $request) => [
            $this->limitPerIp('payment-intents', $request, 20, 10),
            $this->limitByHashedInput('payment-intents-email', $request, 'email', 8, 10),
        ]);
        RateLimiter::for('payment-verify', fn (Request $request) => [
            $this->limitPerIp('payment-verify', $request, 30, 5),
            $this->limitByHashedInput('payment-verify-reference', $request, 'reference', 12, 5),
        ]);
        RateLimiter::for('lenco-webhook', fn (Request $request) => $this->limitPerIp('lenco-webhook', $request, 120, 1));
        RateLimiter::for('admin-login', fn (Request $request) => [
            $this->limitPerIp('admin-login', $request, 10, 15),
            $this->limitByHashedInput('admin-login-email', $request, 'email', 5, 15),
        ]);
    }

    private function limitPerIp(string $prefix, Request $request, int $attempts, int $minutes): Limit
    {
        return Limit::perMinutes($minutes, $attempts)
            ->by($prefix.'|'.($request->ip() ?: 'global'))
            ->response(fn (Request $request, array $headers) => $this->tooManyRequestsResponse($headers));
    }

    private function limitByHashedInput(
        string $prefix,
        Request $request,
        string $input,
        int $attempts,
        int $minutes
    ): Limit {
        $value = strtolower(trim((string) $request->input($input, 'missing')));

        return Limit::perMinutes($minutes, $attempts)
            ->by($prefix.'|'.hash('sha256', $value))
            ->response(fn (Request $request, array $headers) => $this->tooManyRequestsResponse($headers));
    }

    private function tooManyRequestsResponse(array $headers)
    {
        return response()->json([
            'message' => 'Too many requests. Please wait and try again.',
        ], 429, $headers);
    }
}
