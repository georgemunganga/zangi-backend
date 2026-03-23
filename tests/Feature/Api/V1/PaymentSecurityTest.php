<?php

namespace Tests\Feature\Api\V1;

use App\Models\Order;
use App\Models\PortalUser;
use App\Models\PaymentIntent;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_finalizes_payment_when_lenco_amount_and_currency_match(): void
    {
        [$order, $paymentIntent] = $this->createPendingBookPaymentIntent([
            'currency' => 'USD',
            'amount' => 12.80,
            'payment_method' => 'card',
        ]);

        Config::set('services.lenco.secret_key', 'test-secret');
        Config::set('services.lenco.api_base_url', 'https://lenco.test');

        Http::fake([
            'https://lenco.test/collections/status/*' => Http::response([
                'data' => [
                    'status' => 'successful',
                    'amount' => 12.80,
                    'currency' => 'USD',
                    'channel' => 'card',
                ],
            ], 200),
        ]);

        $this->postJson('/api/v1/payments/lenco/verify', [
            'reference' => $paymentIntent->reference,
        ])
            ->assertOk()
            ->assertJsonPath('paid', true)
            ->assertJsonPath('status', 'successful');

        $this->assertSame('Paid', $paymentIntent->fresh()->status);
        $this->assertSame('Paid', $order->fresh()->payment_status);
    }

    public function test_verify_rejects_paid_status_when_lenco_amount_does_not_match(): void
    {
        [$order, $paymentIntent] = $this->createPendingBookPaymentIntent([
            'currency' => 'ZMW',
            'amount' => 358.40,
            'payment_method' => 'card',
        ]);

        Config::set('services.lenco.secret_key', 'test-secret');
        Config::set('services.lenco.api_base_url', 'https://lenco.test');

        Http::fake([
            'https://lenco.test/collections/status/*' => Http::response([
                'data' => [
                    'status' => 'successful',
                    'amount' => 100.00,
                    'currency' => 'ZMW',
                    'channel' => 'card',
                ],
            ], 200),
        ]);

        $this->postJson('/api/v1/payments/lenco/verify', [
            'reference' => $paymentIntent->reference,
        ])
            ->assertOk()
            ->assertJsonPath('paid', false)
            ->assertJsonPath('status', 'failed');

        $this->assertSame('Failed', $paymentIntent->fresh()->status);
        $this->assertSame('Pending', $order->fresh()->payment_status);
    }

    public function test_webhook_does_not_finalize_payment_when_server_side_verification_fails_integrity_checks(): void
    {
        [$order, $paymentIntent] = $this->createPendingBookPaymentIntent([
            'currency' => 'ZMW',
            'amount' => 358.40,
            'payment_method' => 'mobile-money',
        ]);

        Config::set('services.lenco.secret_key', 'test-secret');
        Config::set('services.lenco.webhook_secret', 'webhook-secret');
        Config::set('services.lenco.api_base_url', 'https://lenco.test');

        Http::fake([
            'https://lenco.test/collections/status/*' => Http::response([
                'data' => [
                    'status' => 'successful',
                    'amount' => 200.00,
                    'currency' => 'ZMW',
                    'channel' => 'mobile-money',
                ],
            ], 200),
        ]);

        $payload = [
            'data' => [
                'reference' => $paymentIntent->reference,
                'status' => 'successful',
            ],
        ];
        $signature = hash_hmac('sha256', json_encode($payload), 'webhook-secret');

        $this->postJson('/api/v1/payments/lenco/webhook', $payload, [
            'X-Lenco-Signature' => $signature,
        ])
            ->assertOk()
            ->assertJsonPath('received', true);

        $this->assertSame('Failed', $paymentIntent->fresh()->status);
        $this->assertSame('Pending', $order->fresh()->payment_status);
    }

    public function test_general_payment_intent_rejects_absolute_return_path(): void
    {
        $this->postJson('/api/v1/payments/lenco/intent', [
            'purchaseType' => 'book-order',
            'buyerType' => 'individual',
            'email' => 'buyer@example.com',
            'phone' => '+260000000001',
            'channel' => 'card',
            'currency' => 'USD',
            'returnPath' => 'https://evil.example/collect',
            'customerName' => 'Buyer',
            'metadata' => [
                'productSlug' => 'zangi-flag-of-kindness',
                'formatType' => 'hardcopy',
                'quantity' => 1,
            ],
        ])->assertStatus(422);
    }

    public function test_book_checkout_intent_rejects_absolute_return_path(): void
    {
        $this->postJson('/api/v1/checkout/book-orders/online-intent', [
            'productSlug' => 'zangi-flag-of-kindness',
            'formatType' => 'hardcopy',
            'quantity' => 1,
            'buyerType' => 'individual',
            'email' => 'buyer@example.com',
            'phone' => '+260000000001',
            'currency' => 'USD',
            'paymentMethod' => 'card',
            'returnPath' => 'https://evil.example/collect',
        ])->assertStatus(422);
    }

    private function createPendingBookPaymentIntent(array $overrides = []): array
    {
        $currency = $overrides['currency'] ?? 'USD';
        $amount = $overrides['amount'] ?? 12.80;
        $paymentMethod = $overrides['payment_method'] ?? 'card';
        $referenceSuffix = strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 8));

        $portalUser = PortalUser::create([
            'role' => 'individual',
            'portal_mode' => 'individual',
            'group_type' => null,
            'has_individual_access' => true,
            'has_group_access' => false,
            'name' => 'Buyer Test',
            'email' => "buyer-{$referenceSuffix}@example.com",
            'phone' => '+260000000000',
            'organization_name' => null,
            'headline' => 'Test buyer',
            'notes' => [],
        ]);

        $order = Order::create([
            'reference' => "ZG-{$referenceSuffix}",
            'portal_user_id' => $portalUser->id,
            'buyer_type' => 'individual',
            'email' => $portalUser->email,
            'phone' => $portalUser->phone,
            'organization_name' => null,
            'product_slug' => 'zangi-flag-of-kindness',
            'product_title' => 'Zangi: The Flag of Kindness',
            'format' => 'hardcopy',
            'quantity' => 1,
            'currency' => $currency,
            'unit_price' => $amount,
            'total' => $amount,
            'status' => 'Received',
            'timeline' => ['Received', 'Confirmed', 'Processing', 'Shipped', 'Delivered'],
            'current_step' => 0,
            'payment_status' => 'Pending',
            'payment_method' => $paymentMethod,
            'download_ready' => false,
            'download_path' => null,
        ]);

        $paymentIntent = PaymentIntent::create([
            'reference' => "LEN-{$referenceSuffix}",
            'purchase_type' => 'book-order',
            'purchase_id' => $order->id,
            'buyer_type' => 'individual',
            'email' => $portalUser->email,
            'currency' => $currency,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'status' => 'Pending',
            'lenco_payload' => [
                'productSlug' => 'zangi-flag-of-kindness',
                'formatType' => 'hardcopy',
                'quantity' => 1,
            ],
            'return_path' => '/checkout/zangi-flag-of-kindness?format=hardcopy',
        ]);

        return [$order, $paymentIntent];
    }
}
