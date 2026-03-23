<?php

namespace App\Services;

use App\Models\PaymentIntent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class LencoService
{
    private const PAID_STATUSES = ['successful', 'success', 'paid', 'completed'];
    private const PENDING_STATUSES = ['pending', 'processing', 'confirmation_pending'];

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
        $reportedAmount = $this->normalizeAmount(
            data_get($payload, 'data.amount', data_get($payload, 'amount'))
        );
        $expectedAmount = $this->normalizeAmount($paymentIntent->amount);
        $reportedCurrency = strtoupper((string) data_get(
            $payload,
            'data.currency',
            data_get($payload, 'currency', '')
        ));
        $expectedCurrency = strtoupper((string) $paymentIntent->currency);
        $reportedMethod = $this->normalizePaymentMethod((string) data_get(
            $payload,
            'data.channel',
            data_get(
                $payload,
                'channel',
                data_get(
                    $payload,
                    'data.payment_method',
                    data_get($payload, 'payment_method', '')
                )
            )
        ));
        $expectedMethod = $this->normalizePaymentMethod((string) $paymentIntent->payment_method);
        $rawPaid = in_array($status, self::PAID_STATUSES, true);
        $rawPending = in_array($status, self::PENDING_STATUSES, true);
        $integrityErrors = [];

        if ($rawPaid && ($reportedAmount === null || $expectedAmount === null || abs($reportedAmount - $expectedAmount) > 0.009)) {
            $integrityErrors[] = 'amount_mismatch';
        }

        if ($rawPaid && ($reportedCurrency === '' || $reportedCurrency !== $expectedCurrency)) {
            $integrityErrors[] = 'currency_mismatch';
        }

        if ($rawPaid && $reportedMethod !== '' && $reportedMethod !== $expectedMethod) {
            $integrityErrors[] = 'payment_method_mismatch';
        }

        $integrityOk = $integrityErrors === [];
        $paid = $rawPaid && $integrityOk;
        $pending = ! $paid && $rawPending;

        return [
            'reference' => $paymentIntent->reference,
            'status' => $paid ? 'successful' : ($pending ? 'pending' : 'failed'),
            'paid' => $paid,
            'pending' => $pending,
            'integrity_ok' => $integrityOk,
            'integrity_errors' => $integrityErrors,
            'provider' => 'lenco',
            'currency' => $reportedCurrency !== '' ? $reportedCurrency : $paymentIntent->currency,
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

        $normalizedPath = trim($returnPath);

        if (! str_starts_with($normalizedPath, '/') || str_starts_with($normalizedPath, '//')) {
            return $base;
        }

        return $base.$normalizedPath;
    }

    private function normalizeAmount(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        return round((float) $value, 2);
    }

    private function normalizePaymentMethod(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = str_replace(['_', ' '], '-', $normalized);

        return preg_replace('/-+/', '-', $normalized) ?: '';
    }
}
