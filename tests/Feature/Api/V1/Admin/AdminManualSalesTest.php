<?php

namespace Tests\Feature\Api\V1\Admin;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminManualSalesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-04-01 10:00:00', 'Africa/Lusaka'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_admin_can_create_a_paid_manual_ticket_sale(): void
    {
        $token = AdminUser::factory()->create()->createToken('admin-access')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/v1/admin/manual-sales', [
            'customerMode' => 'walk_in',
            'saleType' => 'ticket',
            'eventSlug' => 'zangi-book-launch-mulungushi-lusaka',
            'ticketType' => 'VIP',
            'buyerName' => 'Counter VIP Buyer',
            'email' => '',
            'phone' => '+260971555101',
            'quantity' => 2,
            'priceMode' => 'standard',
            'paymentMethod' => 'Cash',
            'issueStatus' => 'paid',
            'customerType' => 'Walk-in',
            'relationshipType' => 'Walk-in',
            'notes' => 'Counter sale before event open.',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('saleType', 'ticket')
            ->assertJsonPath('order.source', 'admin_manual')
            ->assertJsonPath('order.paymentStatus', 'paid')
            ->assertJsonPath('tickets.0.quantity', 2)
            ->assertJsonPath('tickets.0.source', 'admin_manual');

        $this->assertDatabaseHas('ticket_purchases', [
            'buyer_name' => 'Counter VIP Buyer',
            'source' => 'admin_manual',
            'status' => 'Ticket Ready',
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('payment_intents', [
            'purchase_type' => 'event-ticket',
            'payment_method' => 'cash',
            'status' => 'Paid',
        ]);
    }

    public function test_admin_can_create_a_reserved_manual_book_sale(): void
    {
        $token = AdminUser::factory()->create()->createToken('admin-access')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/v1/admin/manual-sales', [
            'customerMode' => 'walk_in',
            'saleType' => 'book',
            'bookSlug' => 'zangi-flag-of-kindness',
            'bookFormat' => 'Hardcopy',
            'buyerName' => 'Book Counter Buyer',
            'email' => 'books@example.com',
            'phone' => '+260971555202',
            'quantity' => 1,
            'priceMode' => 'custom',
            'customUnitPrice' => 420,
            'paymentMethod' => 'Card',
            'issueStatus' => 'reserved',
            'customerType' => 'Individual',
            'relationshipType' => 'Walk-in',
            'notes' => 'Hold until courier batch closes.',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('saleType', 'book')
            ->assertJsonPath('order.source', 'admin_manual')
            ->assertJsonPath('order.status', 'pending')
            ->assertJsonPath('order.total', 420);

        $this->assertDatabaseHas('orders', [
            'buyer_name' => 'Book Counter Buyer',
            'source' => 'admin_manual',
            'status' => 'Received',
            'payment_status' => 'Pending',
            'total' => 420,
        ]);

        $this->assertDatabaseHas('payment_intents', [
            'purchase_type' => 'book-order',
            'payment_method' => 'card',
            'status' => 'Pending',
            'amount' => 420,
        ]);
    }

    public function test_manual_sale_requires_contact_identity(): void
    {
        $token = AdminUser::factory()->create()->createToken('admin-access')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/admin/manual-sales', [
            'customerMode' => 'walk_in',
            'saleType' => 'ticket',
            'eventSlug' => 'zangi-book-launch-mulungushi-lusaka',
            'ticketType' => 'VIP',
            'buyerName' => 'Counter VIP Buyer',
            'email' => '',
            'phone' => '',
            'quantity' => 1,
            'priceMode' => 'standard',
            'paymentMethod' => 'Cash',
            'issueStatus' => 'paid',
        ])->assertStatus(422);
    }
}
