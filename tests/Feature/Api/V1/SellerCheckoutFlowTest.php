<?php

namespace Tests\Feature\Api\V1;

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

class SellerCheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_mobile_money_intent_creates_pending_seller_sale_records(): void
    {
        Config::set('services.lenco.public_key', 'pub-test');
        $seller = $this->createSeller();
        Sanctum::actingAs($seller, [], 'seller');

        $this->travelTo(Carbon::parse('2026-04-20 10:00:00', 'Africa/Lusaka'));

        $response = $this->postJson('/api/v1/seller/checkout/mobile-money-intent', [
            'eventId' => 'zangi-book-launch-mulungushi-lusaka',
            'ticketTypeId' => 'standard',
            'quantity' => 2,
            'buyerPhone' => '0972827372',
            'buyerEmail' => 'field.buyer@gmail.com',
            'buyerName' => 'Field Buyer',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('amount', 600)
            ->assertJsonPath('sale.paymentMethod', 'Mobile Money')
            ->assertJsonPath('sale.paymentStatus', 'Pending')
            ->assertJsonPath('share.eventUrl', 'https://www.zangisworld.com/events/zangi-book-launch-mulungushi-lusaka');

        $ticketPurchase = TicketPurchase::query()->firstOrFail();
        $paymentIntent = PaymentIntent::query()->firstOrFail();

        $this->assertSame($seller->id, $ticketPurchase->seller_id);
        $this->assertSame('seller_terminal', $ticketPurchase->source);
        $this->assertSame('Pending', $ticketPurchase->status);
        $this->assertEquals(300.00, (float) $ticketPurchase->unit_price);
        $this->assertSame('mobile-money', $paymentIntent->payment_method);
        $this->assertSame('Pending', $paymentIntent->status);
        $this->assertSame('seller_terminal', data_get($paymentIntent->lenco_payload, 'source'));
    }

    public function test_seller_can_verify_mobile_money_payment_and_issue_ticket(): void
    {
        Config::set('services.lenco.public_key', 'pub-test');
        Config::set('services.lenco.secret_key', 'test-secret');
        Config::set('services.lenco.api_base_url', 'https://lenco.test');
        Mail::fake();

        $seller = $this->createSeller();
        Sanctum::actingAs($seller, [], 'seller');

        $this->travelTo(Carbon::parse('2026-05-10 12:00:00', 'Africa/Lusaka'));

        $intentResponse = $this->postJson('/api/v1/seller/checkout/mobile-money-intent', [
                'eventId' => 'zangi-book-launch-mulungushi-lusaka',
                'ticketTypeId' => 'standard',
                'quantity' => 1,
                'buyerPhone' => '0972827372',
                'buyerEmail' => 'field.verify@gmail.com',
                'buyerName' => 'Verify Buyer',
            ])
            ->assertCreated();

        $reference = (string) $intentResponse->json('reference');

        Http::fake([
            'https://lenco.test/collections/status/*' => Http::response([
                'data' => [
                    'status' => 'successful',
                    'amount' => 350.00,
                    'currency' => 'ZMW',
                    'channel' => 'mobile-money',
                ],
            ], 200),
        ]);

        $this->postJson('/api/v1/seller/checkout/verify', [
            'reference' => $reference,
        ])
            ->assertOk()
            ->assertJsonPath('paid', true)
            ->assertJsonPath('sale.paymentStatus', 'Paid')
            ->assertJsonPath('sale.paymentMethod', 'Mobile Money');

        $ticketPurchase = TicketPurchase::query()->firstOrFail();
        $paymentIntent = PaymentIntent::query()->firstOrFail();

        $this->assertSame('Ticket Ready', $ticketPurchase->status);
        $this->assertNotNull($ticketPurchase->ticket_code);
        $this->assertSame('Paid', $paymentIntent->status);
    }

    public function test_seller_can_confirm_manual_deposit_and_sales_read_reflects_it(): void
    {
        $seller = $this->createSeller();
        Sanctum::actingAs($seller, [], 'seller');

        $this->travelTo(Carbon::parse('2026-03-29 09:00:00', 'Africa/Lusaka'));

        $this->postJson('/api/v1/seller/checkout/manual-deposit', [
                'eventId' => 'zangi-book-launch-mulungushi-lusaka',
                'ticketTypeId' => 'standard',
                'quantity' => 1,
                'buyerPhone' => '0972827372',
                'buyerName' => 'Deposit Buyer',
                'depositReference' => 'DEP-123',
            ])
            ->assertCreated()
            ->assertJsonPath('sale.paymentMethod', 'Manual Deposit')
            ->assertJsonPath('sale.paymentStatus', 'Paid')
            ->assertJsonPath('share.depositPhone', '+260972931526');

        $this->getJson('/api/v1/seller/sales')
            ->assertOk()
            ->assertJsonPath('sales.0.paymentMethod', 'Manual Deposit')
            ->assertJsonPath('sales.0.paymentStatus', 'Paid');

        $ticketPurchase = TicketPurchase::query()->firstOrFail();
        $paymentIntent = PaymentIntent::query()->firstOrFail();

        $this->assertSame('Ticket Ready', $ticketPurchase->status);
        $this->assertSame('manual-deposit', $paymentIntent->payment_method);
        $this->assertSame('Paid', $paymentIntent->status);
    }

    public function test_seller_login_token_authenticates_protected_get_endpoints(): void
    {
        $this->createSeller();

        $loginResponse = $this->postJson('/api/v1/seller/auth/login', [
            'phone' => '0971000001',
            'pin' => '1234',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonPath('seller.phone', '0971000001');

        $token = (string) $loginResponse->json('accessToken');

        $this->withToken($token)
            ->getJson('/api/v1/seller/events/active')
            ->assertOk()
            ->assertJsonPath('event.slug', 'zangi-book-launch-mulungushi-lusaka');

        $this->withToken($token)
            ->getJson('/api/v1/seller/ticket-types')
            ->assertOk()
            ->assertJsonStructure([
                'ticketTypes',
                'currentRound',
            ]);

        $this->withToken($token)
            ->getJson('/api/v1/seller/auth/me')
            ->assertOk()
            ->assertJsonPath('seller.phone', '0971000001');
    }

    public function test_real_seller_login_token_supports_the_active_seller_app_api_surface(): void
    {
        Mail::fake();
        $this->travelTo(Carbon::parse('2026-04-20 09:00:00', 'Africa/Lusaka'));
        $this->createSeller();

        $token = (string) $this->postJson('/api/v1/seller/auth/login', [
            'phone' => '0971000001',
            'pin' => '1234',
        ])->assertOk()->json('accessToken');

        $this->withToken($token)
            ->getJson('/api/v1/seller/dashboard')
            ->assertOk()
            ->assertJsonPath('today.salesCount', 0)
            ->assertJsonPath('event.name', "Zangi's Flag Book Launch");

        $this->withToken($token)
            ->getJson('/api/v1/seller/current-round')
            ->assertOk()
            ->assertJsonStructure([
                'key',
                'label',
                'publicLabel',
                'startsAt',
                'endsAt',
                'priceZmw',
            ]);

        $this->withToken($token)
            ->getJson('/api/v1/seller/events/zangi-book-launch-mulungushi-lusaka')
            ->assertOk()
            ->assertJsonPath('event.publicEventUrl', 'https://www.zangisworld.com/events/zangi-book-launch-mulungushi-lusaka');

        $saleResponse = $this->withToken($token)
            ->postJson('/api/v1/seller/checkout/manual-deposit', [
                'eventId' => 'zangi-book-launch-mulungushi-lusaka',
                'ticketTypeId' => 'standard',
                'quantity' => 1,
                'buyerPhone' => '0972827372',
                'buyerName' => 'Surface Check Buyer',
                'depositReference' => 'DEP-SURFACE-001',
            ])
            ->assertCreated()
            ->assertJsonPath('sale.paymentMethod', 'Manual Deposit');

        $saleId = (int) $saleResponse->json('sale.id');

        $this->withToken($token)
            ->getJson('/api/v1/seller/sales')
            ->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('sales.0.id', $saleId);

        $this->withToken($token)
            ->getJson('/api/v1/seller/sales/recent')
            ->assertOk()
            ->assertJsonPath('sales.0.id', $saleId);

        $this->withToken($token)
            ->getJson("/api/v1/seller/sales/{$saleId}")
            ->assertOk()
            ->assertJsonPath('sale.id', $saleId)
            ->assertJsonPath('sale.paymentStatus', 'Paid');

        $this->withToken($token)
            ->postJson("/api/v1/seller/sales/{$saleId}/email/resend")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->withToken($token)
            ->postJson('/api/v1/seller/auth/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);

        app('auth')->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/v1/seller/events/active')
            ->assertUnauthorized();
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
}
