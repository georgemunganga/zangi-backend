<?php

namespace Tests\Feature\Api\V1;

use App\Mail\EventTicketConfirmationMail;
use App\Models\PaymentIntent;
use App\Models\TicketPurchase;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EventTicketPricingRoundTest extends TestCase
{
    use RefreshDatabase;

    public function test_standard_ticket_round_prices_switch_across_boundary_dates(): void
    {
        Config::set('services.lenco.public_key', 'pub-test');

        $this->travelTo(Carbon::parse('2026-03-25 09:00:00', 'Africa/Lusaka'));
        $this->postJson('/api/v1/payments/lenco/intent', $this->eventIntentPayload([
            'email' => 'early.ticket@gmail.com',
        ]))
            ->assertCreated()
            ->assertJsonPath('amount', 250);
        $this->assertDatabaseHas('ticket_purchases', [
            'email' => 'early.ticket@gmail.com',
            'unit_price' => 250.00,
            'pricing_round_key' => 'early_bird',
            'pricing_round_label' => 'Early Bird',
        ]);

        $this->travelTo(Carbon::parse('2026-04-15 09:00:00', 'Africa/Lusaka'));
        $this->postJson('/api/v1/payments/lenco/intent', $this->eventIntentPayload([
            'email' => 'standard.ticket@gmail.com',
        ]))
            ->assertCreated()
            ->assertJsonPath('amount', 300);
        $this->assertDatabaseHas('ticket_purchases', [
            'email' => 'standard.ticket@gmail.com',
            'unit_price' => 300.00,
            'pricing_round_key' => 'standard',
            'pricing_round_label' => 'Standard',
        ]);

        $this->travelTo(Carbon::parse('2026-05-05 09:00:00', 'Africa/Lusaka'));
        $this->postJson('/api/v1/payments/lenco/intent', $this->eventIntentPayload([
            'email' => 'late.ticket@gmail.com',
        ]))
            ->assertCreated()
            ->assertJsonPath('amount', 350);
        $this->assertDatabaseHas('ticket_purchases', [
            'email' => 'late.ticket@gmail.com',
            'unit_price' => 350.00,
            'pricing_round_key' => 'late',
            'pricing_round_label' => 'Late',
        ]);
    }

    public function test_vip_ticket_price_stays_fixed_while_round_metadata_tracks_sale_window(): void
    {
        Config::set('services.lenco.public_key', 'pub-test');

        $this->travelTo(Carbon::parse('2026-04-14 11:00:00', 'Africa/Lusaka'));
        $this->postJson('/api/v1/payments/lenco/intent', $this->eventIntentPayload([
            'email' => 'vip.early.ticket@gmail.com',
            'metadata' => [
                'eventSlug' => 'zangi-book-launch-mulungushi-lusaka',
                'ticketTypeId' => 'vip',
                'quantity' => 1,
            ],
        ]))
            ->assertCreated()
            ->assertJsonPath('amount', 500);
        $this->assertDatabaseHas('ticket_purchases', [
            'email' => 'vip.early.ticket@gmail.com',
            'unit_price' => 500.00,
            'pricing_round_key' => 'early_bird',
            'pricing_round_label' => 'Early Bird',
        ]);

        $this->travelTo(Carbon::parse('2026-05-24 14:00:00', 'Africa/Lusaka'));
        $this->postJson('/api/v1/payments/lenco/intent', $this->eventIntentPayload([
            'email' => 'vip.late.ticket@gmail.com',
            'metadata' => [
                'eventSlug' => 'zangi-book-launch-mulungushi-lusaka',
                'ticketTypeId' => 'vip',
                'quantity' => 1,
            ],
        ]))
            ->assertCreated()
            ->assertJsonPath('amount', 500);
        $this->assertDatabaseHas('ticket_purchases', [
            'email' => 'vip.late.ticket@gmail.com',
            'unit_price' => 500.00,
            'pricing_round_key' => 'late',
            'pricing_round_label' => 'Late',
        ]);
    }

    public function test_event_ticket_sales_are_blocked_before_opening_and_after_closing(): void
    {
        Config::set('services.lenco.public_key', 'pub-test');

        $this->travelTo(Carbon::parse('2026-03-24 09:00:00', 'Africa/Lusaka'));
        $this->postJson('/api/v1/payments/lenco/intent', $this->eventIntentPayload())
            ->assertStatus(422)
            ->assertJsonPath('message', 'Ticket sales open on March 25, 2026.');

        $this->travelTo(Carbon::parse('2026-05-25 09:00:00', 'Africa/Lusaka'));
        $this->postJson('/api/v1/payments/lenco/intent', $this->eventIntentPayload())
            ->assertStatus(422)
            ->assertJsonPath('message', 'Ticket sales for this event have closed.');
    }

    public function test_event_ticket_checkout_rejects_usd_and_sends_confirmation_after_paid_verification(): void
    {
        Config::set('services.lenco.public_key', 'pub-test');

        $this->travelTo(Carbon::parse('2026-05-24 12:00:00', 'Africa/Lusaka'));

        $this->postJson('/api/v1/payments/lenco/intent', $this->eventIntentPayload([
            'currency' => 'USD',
            'channel' => 'card',
            'email' => 'usd.intent.ticket@gmail.com',
        ]))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Event tickets are only available in Zambian Kwacha.');

        $this->postJson('/api/v1/checkout/event-tickets/online-intent', [
            'eventSlug' => 'zangi-book-launch-mulungushi-lusaka',
            'ticketTypeId' => 'standard',
            'quantity' => 1,
            'buyerType' => 'individual',
            'email' => 'usd.direct.ticket@gmail.com',
            'phone' => '+260971000002',
            'currency' => 'USD',
            'paymentMethod' => 'card',
            'returnPath' => '/events/zangi-book-launch-mulungushi-lusaka/checkout?ticket=standard',
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Event tickets are only available in Zambian Kwacha.');

        Config::set('services.lenco.secret_key', 'test-secret');
        Config::set('services.lenco.api_base_url', 'https://lenco.test');
        Mail::fake();

        $this->postJson('/api/v1/payments/lenco/intent', $this->eventIntentPayload([
            'email' => 'paid.ticket@gmail.com',
        ]))->assertCreated();

        $paymentIntent = PaymentIntent::query()->where('email', 'paid.ticket@gmail.com')->latest()->firstOrFail();
        $ticketPurchase = TicketPurchase::query()->where('email', 'paid.ticket@gmail.com')->latest()->firstOrFail();

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

        $this->postJson('/api/v1/payments/lenco/verify', [
            'reference' => $paymentIntent->reference,
        ])
            ->assertOk()
            ->assertJsonPath('paid', true)
            ->assertJsonPath('status', 'successful');

        $ticketPurchase->refresh();
        $paymentIntent->refresh();

        $this->assertSame('Ticket Ready', $ticketPurchase->status);
        $this->assertSame('late', $ticketPurchase->pricing_round_key);
        $this->assertSame('Late', $ticketPurchase->pricing_round_label);
        $this->assertSame('late', data_get($paymentIntent->lenco_payload, 'pricingRoundKey'));
        $this->assertSame('Late', data_get($paymentIntent->lenco_payload, 'pricingRoundLabel'));

        Mail::assertSent(EventTicketConfirmationMail::class, function (EventTicketConfirmationMail $mail) use ($ticketPurchase): bool {
            return $mail->hasTo('paid.ticket@gmail.com')
                && $mail->ticketPurchase->reference === $ticketPurchase->reference
                && $mail->ticketPurchase->pricing_round_label === 'Late'
                && count($mail->attachments()) === 1;
        });
    }

    public function test_legacy_event_slug_is_still_accepted_for_ticket_intents(): void
    {
        Config::set('services.lenco.public_key', 'pub-test');

        $this->travelTo(Carbon::parse('2026-04-20 09:00:00', 'Africa/Lusaka'));

        $this->postJson('/api/v1/payments/lenco/intent', $this->eventIntentPayload([
            'email' => 'legacy.slug.ticket@gmail.com',
            'metadata' => [
                'eventSlug' => 'zangi-book-launch-NIPA-lusaka',
                'ticketTypeId' => 'standard',
                'quantity' => 1,
            ],
        ]))
            ->assertCreated()
            ->assertJsonPath('amount', 300);

        $this->assertDatabaseHas('ticket_purchases', [
            'email' => 'legacy.slug.ticket@gmail.com',
            'event_slug' => 'zangi-book-launch-mulungushi-lusaka',
            'unit_price' => 300.00,
        ]);
    }

    private function eventIntentPayload(array $overrides = []): array
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
}
