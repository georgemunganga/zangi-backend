<?php

namespace Tests\Feature\Api\V1;

use App\Models\AdminUser;
use App\Models\PaymentIntent;
use App\Models\Seller;
use App\Models\TicketPurchase;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TicketFlowMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-04-20 09:00:00', 'Africa/Lusaka'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_paid_ticket_records_stay_distinct_across_website_admin_and_seller_flows(): void
    {
        Config::set('services.lenco.public_key', 'pub-test');
        Config::set('services.lenco.secret_key', 'test-secret');
        Config::set('services.lenco.api_base_url', 'https://lenco.test');
        Mail::fake();

        $adminToken = AdminUser::factory()->create()->createToken('admin-access')->plainTextToken;
        $seller = $this->createSeller();

        $this->postJson('/api/v1/payments/lenco/intent', $this->websiteEventIntentPayload([
            'email' => 'website.buyer@gmail.com',
        ]))->assertCreated();

        $websitePaymentIntent = PaymentIntent::query()->where('email', 'website.buyer@gmail.com')->latest()->firstOrFail();
        $websiteTicketPurchase = TicketPurchase::query()->where('email', 'website.buyer@gmail.com')->latest()->firstOrFail();

        Http::fake(fn ($request) => $this->successfulLencoCollectionResponse(
            basename((string) $request->url())
        ));

        $this->postJson('/api/v1/payments/lenco/verify', [
            'reference' => $websitePaymentIntent->reference,
        ])->assertOk()->assertJsonPath('paid', true);

        $adminManualResponse = $this->withToken($adminToken)->postJson('/api/v1/admin/manual-sales', [
            'customerMode' => 'walk_in',
            'saleType' => 'ticket',
            'eventSlug' => 'zangi-book-launch-mulungushi-lusaka',
            'ticketType' => 'Standard',
            'buyerName' => 'Admin Counter Buyer',
            'email' => '',
            'phone' => '+260971555101',
            'quantity' => 1,
            'priceMode' => 'standard',
            'paymentMethod' => 'Cash',
            'issueStatus' => 'paid',
            'customerType' => 'Walk-in',
            'relationshipType' => 'Walk-in',
            'notes' => 'Admin counter ticket sale.',
        ])->assertCreated();

        $adminTicketPurchaseId = (int) $adminManualResponse->json('tickets.0.id');
        $adminPaymentIntentId = (int) $adminManualResponse->json('payment.id');

        Sanctum::actingAs($seller, [], 'seller');
        $sellerIntentResponse = $this->postJson('/api/v1/seller/checkout/mobile-money-intent', [
            'eventId' => 'zangi-book-launch-mulungushi-lusaka',
            'ticketTypeId' => 'standard',
            'quantity' => 1,
            'buyerPhone' => '0972827372',
            'buyerEmail' => 'seller.buyer@gmail.com',
            'buyerName' => 'Seller Buyer',
        ])->assertCreated();

        $sellerReference = (string) $sellerIntentResponse->json('reference');

        $this->postJson('/api/v1/seller/checkout/verify', [
            'reference' => $sellerReference,
        ])->assertOk()->assertJsonPath('paid', true);

        $sellerPaymentIntent = PaymentIntent::query()->where('reference', $sellerReference)->firstOrFail();
        $sellerTicketPurchase = TicketPurchase::query()->where('email', 'seller.buyer@gmail.com')->latest()->firstOrFail();
        $adminManualTicketPurchase = TicketPurchase::query()->findOrFail($adminTicketPurchaseId);
        $adminManualPaymentIntent = PaymentIntent::query()->findOrFail($adminPaymentIntentId);

        $this->assertSame('online', $websiteTicketPurchase->source ?: 'online');
        $this->assertSame('admin_manual', $adminManualTicketPurchase->source);
        $this->assertSame('seller_terminal', $sellerTicketPurchase->source);

        $ticketsResponse = $this->withToken($adminToken)->getJson('/api/v1/admin/tickets')
            ->assertOk()
            ->assertJsonPath('meta.total', 3);

        $ticketsResponse
            ->assertJsonFragment([
                'reference' => $websiteTicketPurchase->reference,
                'source' => 'online',
                'paymentMethod' => 'Mobile Money',
                'paymentStatus' => 'paid',
            ])
            ->assertJsonFragment([
                'reference' => $adminManualTicketPurchase->reference,
                'source' => 'admin_manual',
                'paymentMethod' => 'Cash',
                'paymentStatus' => 'paid',
            ])
            ->assertJsonFragment([
                'reference' => $sellerTicketPurchase->reference,
                'source' => 'seller_terminal',
                'paymentMethod' => 'Mobile Money',
                'paymentStatus' => 'paid',
            ]);

        $ordersResponse = $this->withToken($adminToken)->getJson('/api/v1/admin/orders')
            ->assertOk()
            ->assertJsonPath('meta.total', 3);

        $ordersResponse
            ->assertJsonFragment([
                'reference' => $websiteTicketPurchase->reference,
                'source' => 'online',
                'paymentMethod' => 'Mobile Money',
                'paymentStatus' => 'paid',
            ])
            ->assertJsonFragment([
                'reference' => $adminManualTicketPurchase->reference,
                'source' => 'admin_manual',
                'paymentMethod' => 'Cash',
                'paymentStatus' => 'paid',
            ])
            ->assertJsonFragment([
                'reference' => $sellerTicketPurchase->reference,
                'source' => 'seller_terminal',
                'paymentMethod' => 'Mobile Money',
                'paymentStatus' => 'paid',
            ]);

        $paymentsResponse = $this->withToken($adminToken)->getJson('/api/v1/admin/payments')
            ->assertOk()
            ->assertJsonPath('meta.total', 3);

        $paymentsResponse
            ->assertJsonFragment([
                'reference' => $websitePaymentIntent->reference,
                'source' => 'online',
                'method' => 'Mobile Money',
                'status' => 'paid',
            ])
            ->assertJsonFragment([
                'reference' => $adminManualPaymentIntent->reference,
                'source' => 'admin_manual',
                'method' => 'Cash',
                'status' => 'paid',
            ])
            ->assertJsonFragment([
                'reference' => $sellerPaymentIntent->reference,
                'source' => 'seller_terminal',
                'method' => 'Mobile Money',
                'status' => 'paid',
            ]);
    }

    public function test_pending_ticket_visibility_is_channel_specific_until_payment_is_confirmed(): void
    {
        Config::set('services.lenco.public_key', 'pub-test');

        $adminToken = AdminUser::factory()->create()->createToken('admin-access')->plainTextToken;
        $seller = $this->createSeller();

        $this->postJson('/api/v1/payments/lenco/intent', $this->websiteEventIntentPayload([
            'email' => 'website.pending@gmail.com',
        ]))->assertCreated();

        $websitePaymentIntent = PaymentIntent::query()->where('email', 'website.pending@gmail.com')->latest()->firstOrFail();
        $websiteTicketPurchase = TicketPurchase::query()->where('email', 'website.pending@gmail.com')->latest()->firstOrFail();

        $adminManualResponse = $this->withToken($adminToken)->postJson('/api/v1/admin/manual-sales', [
            'customerMode' => 'walk_in',
            'saleType' => 'ticket',
            'eventSlug' => 'zangi-book-launch-mulungushi-lusaka',
            'ticketType' => 'Standard',
            'buyerName' => 'Reserved Counter Buyer',
            'email' => '',
            'phone' => '+260971555202',
            'quantity' => 1,
            'priceMode' => 'standard',
            'paymentMethod' => 'Card',
            'issueStatus' => 'reserved',
            'customerType' => 'Walk-in',
            'relationshipType' => 'Walk-in',
            'notes' => 'Reserved admin ticket.',
        ])->assertCreated();

        $adminTicketPurchase = TicketPurchase::query()->findOrFail((int) $adminManualResponse->json('tickets.0.id'));
        $adminPaymentIntent = PaymentIntent::query()->findOrFail((int) $adminManualResponse->json('payment.id'));

        Sanctum::actingAs($seller, [], 'seller');
        $sellerIntentResponse = $this->postJson('/api/v1/seller/checkout/mobile-money-intent', [
            'eventId' => 'zangi-book-launch-mulungushi-lusaka',
            'ticketTypeId' => 'standard',
            'quantity' => 1,
            'buyerPhone' => '0972827372',
            'buyerEmail' => 'seller.pending@gmail.com',
            'buyerName' => 'Pending Seller Buyer',
        ])->assertCreated();

        $sellerPaymentIntent = PaymentIntent::query()->where('reference', (string) $sellerIntentResponse->json('reference'))->firstOrFail();
        $sellerTicketPurchase = TicketPurchase::query()->where('email', 'seller.pending@gmail.com')->latest()->firstOrFail();

        $ticketsResponse = $this->withToken($adminToken)->getJson('/api/v1/admin/tickets')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $ticketsResponse
            ->assertJsonFragment([
                'reference' => $adminTicketPurchase->reference,
                'source' => 'admin_manual',
                'paymentStatus' => 'pending',
            ])
            ->assertJsonMissing(['reference' => $websiteTicketPurchase->reference])
            ->assertJsonMissing(['reference' => $sellerTicketPurchase->reference]);

        $ordersResponse = $this->withToken($adminToken)->getJson('/api/v1/admin/orders')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $ordersResponse
            ->assertJsonFragment([
                'reference' => $adminTicketPurchase->reference,
                'source' => 'admin_manual',
                'paymentStatus' => 'pending',
            ])
            ->assertJsonMissing(['reference' => $websiteTicketPurchase->reference])
            ->assertJsonMissing(['reference' => $sellerTicketPurchase->reference]);

        $paymentsResponse = $this->withToken($adminToken)->getJson('/api/v1/admin/payments')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $paymentsResponse
            ->assertJsonFragment([
                'reference' => $adminPaymentIntent->reference,
                'source' => 'admin_manual',
                'status' => 'pending',
            ])
            ->assertJsonMissing(['reference' => $websitePaymentIntent->reference])
            ->assertJsonMissing(['reference' => $sellerPaymentIntent->reference]);
    }

    private function websiteEventIntentPayload(array $overrides = []): array
    {
        $payload = [
            'purchaseType' => 'event-ticket',
            'buyerType' => 'individual',
            'email' => 'buyer.ticket@gmail.com',
            'phone' => '+260971000001',
            'channel' => 'mobile-money',
            'currency' => 'ZMW',
            'returnPath' => '/events/zangi-book-launch-mulungushi-lusaka/checkout?ticket=standard',
            'customerName' => 'Buyer',
            'metadata' => [
                'eventSlug' => 'zangi-book-launch-mulungushi-lusaka',
                'ticketTypeId' => 'standard',
                'quantity' => 1,
            ],
        ];

        return array_replace_recursive($payload, $overrides);
    }

    private function createSeller(): Seller
    {
        return Seller::query()->create([
            'name' => 'Field Agent',
            'code' => 'AGT-001',
            'phone' => '0971000001',
            'pin_hash' => Hash::make('1234'),
            'status' => 'active',
        ]);
    }

    private function successfulLencoCollectionResponse(string $reference)
    {
        $paymentIntent = PaymentIntent::query()->where('reference', $reference)->firstOrFail();

        return Http::response([
            'data' => [
                'status' => 'successful',
                'amount' => (float) $paymentIntent->amount,
                'currency' => $paymentIntent->currency,
                'channel' => $paymentIntent->payment_method === 'mobile-money'
                    ? 'mobile-money'
                    : 'card',
            ],
        ], 200);
    }
}
