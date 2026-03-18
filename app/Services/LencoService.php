<?php

namespace App\Services;

use App\Models\PaymentIntent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class LencoService
{
    public function buildWidgetIntent(PaymentIntent $paymentIntent, array $channels): array
    {
        $publicKey = (string) config('services.lenco.public_key');

        if ($publicKey === '') {
            throw new RuntimeException('LENCO_PUBLIC_KEY is not configured.');
        }

        return [
            'reference' => $paymentIntent->reference,
            'amount' => (float) $paymentIntent->amount,
            'currency' => $paymentIntent->currency,
            'publicKey' => $publicKey,
            'channels' => array_values($channels),
            'redirectUrl' => $this->redirectUrl($paymentIntent->return_path),
        ];
    }

    public function verifyCollection(PaymentIntent $paymentIntent): array
    {
        $secretKey = (string) config('services.lenco.secret_key');

        if ($secretKey === '') {
            throw new RuntimeException('LENCO_SECRET_KEY is not configured.');
        }

        $response = Http::withToken($secretKey)
            ->acceptJson()
            ->get(config('services.lenco.api_base_url').'/collections/status/'.$paymentIntent->reference);

        if (! $response->successful()) {
            throw new RuntimeException('Lenco verification failed.');
        }

        $payload = $response->json() ?? [];
        $status = strtolower((string) data_get($payload, 'data.status', data_get($payload, 'status', 'pending')));
        $paid = in_array($status, ['successful', 'success', 'paid', 'completed'], true);
        $pending = in_array($status, ['pending', 'processing', 'confirmation_pending'], true);

        return [
            'reference' => $paymentIntent->reference,
            'status' => $paid ? 'successful' : ($pending ? 'pending' : 'failed'),
            'paid' => $paid,
            'pending' => $pending,
            'provider' => 'lenco',
            'currency' => data_get($payload, 'data.currency', $paymentIntent->currency),
            'verifiedAt' => now()->toIso8601String(),
            'lencoResponse' => $payload,
            'maskedAccount' => data_get($payload, 'data.customer.masked_account', ''),
            'accountName' => data_get($payload, 'data.customer.name', ''),
            'methodLabel' => Str::headline(str_replace('-', ' ', $paymentIntent->payment_method)),
        ];
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $secret = (string) config('services.lenco.webhook_secret');

        if ($secret === '') {
            return false;
        }

        $providedSignature = (string) ($request->header('X-Lenco-Signature') ?: $request->header('x-lenco-signature'));

        if ($providedSignature === '') {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expectedSignature, $providedSignature);
    }

    private function redirectUrl(?string $returnPath): string
    {
        $base = rtrim((string) config('services.lenco.redirect_base_url'), '/');

        if (! $returnPath) {
            return $base;
        }

        if (preg_match('/^https?:\/\//i', $returnPath)) {
            return $returnPath;
        }

        return $base.'/'.ltrim($returnPath, '/');
    }
}
