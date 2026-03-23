<?php

namespace Tests\Feature\Api\V1\Admin;

use Carbon\Carbon;
use App\Models\AdminUser;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminPaymentActionsTest extends TestCase
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

    public function test_admin_can_reconcile_and_refund_payments(): void
    {
        $token = AdminUser::factory()->create()->createToken('admin-access')->plainTextToken;

        $pending = $this->withToken($token)->postJson('/api/v1/admin/manual-sales', [
            'customerMode' => 'walk_in',
            'saleType' => 'ticket',
            'eventSlug' => 'zangi-book-launch-mulungushi-lusaka',
            'ticketType' => 'VIP',
            'buyerName' => 'Payment Reconcile Buyer',
            'email' => 'reconcile@example.com',
            'phone' => '+260971555808',
            'quantity' => 1,
            'priceMode' => 'standard',
            'paymentMethod' => 'Cash',
            'issueStatus' => 'reserved',
            'customerType' => 'Walk-in',
            'relationshipType' => 'Walk-in',
        ])->assertCreated();

        $this->withToken($token)->postJson("/api/v1/admin/payments/{$pending->json('payment.id')}/reconcile")
            ->assertOk()
            ->assertJsonPath('payment.status', 'paid');

        $paid = $this->withToken($token)->postJson('/api/v1/admin/manual-sales', [
            'customerMode' => 'walk_in',
            'saleType' => 'book',
            'bookSlug' => 'zangi-flag-of-kindness',
            'bookFormat' => 'Digital',
            'buyerName' => 'Payment Refund Buyer',
            'email' => 'payment-refund@example.com',
            'phone' => '+260971555909',
            'quantity' => 1,
            'priceMode' => 'standard',
            'paymentMethod' => 'Cash',
            'issueStatus' => 'paid',
            'customerType' => 'Individual',
            'relationshipType' => 'Walk-in',
        ])->assertCreated();

        $this->withToken($token)->postJson("/api/v1/admin/payments/{$paid->json('payment.id')}/refund")
            ->assertOk()
            ->assertJsonPath('payment.status', 'refunded');
    }

    public function test_admin_can_mark_payment_failed_and_attach_note(): void
    {
        $token = AdminUser::factory()->create()->createToken('admin-access')->plainTextToken;

        $created = $this->withToken($token)->postJson('/api/v1/admin/manual-sales', [
            'customerMode' => 'walk_in',
            'saleType' => 'book',
            'bookSlug' => 'zangi-flag-of-kindness',
            'bookFormat' => 'Hardcopy',
            'buyerName' => 'Payment Failure Buyer',
            'email' => 'payment-failed@example.com',
            'phone' => '+260971555010',
            'quantity' => 1,
            'priceMode' => 'standard',
            'paymentMethod' => 'Card',
            'issueStatus' => 'reserved',
            'customerType' => 'Individual',
            'relationshipType' => 'Walk-in',
        ])->assertCreated();

        $paymentId = $created->json('payment.id');

        $this->withToken($token)->postJson("/api/v1/admin/payments/{$paymentId}/mark-failed")
            ->assertOk()
            ->assertJsonPath('payment.status', 'failed');

        $this->withToken($token)->postJson("/api/v1/admin/payments/{$paymentId}/note", [
            'note' => 'Awaiting callback from the payer.',
        ])
            ->assertOk()
            ->assertJsonPath('payment.notes', 'Awaiting callback from the payer.');
    }
}
