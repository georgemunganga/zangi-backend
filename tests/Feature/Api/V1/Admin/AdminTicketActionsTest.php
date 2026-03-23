<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\AdminUser;
use App\Mail\TicketPassMail;
use App\Models\TicketPurchase;
use Tests\TestCase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminTicketActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_mark_a_paid_ticket_as_used(): void
    {
        $token = AdminUser::factory()->create()->createToken('admin-access')->plainTextToken;

        $created = $this->withToken($token)->postJson('/api/v1/admin/manual-sales', [
            'customerMode' => 'walk_in',
            'saleType' => 'ticket',
            'eventSlug' => 'zangi-book-launch-mulungushi-lusaka',
            'ticketType' => 'VIP',
            'buyerName' => 'Gate Check Buyer',
            'email' => 'gate@example.com',
            'phone' => '+260971555303',
            'quantity' => 1,
            'priceMode' => 'standard',
            'paymentMethod' => 'Cash',
            'issueStatus' => 'paid',
            'customerType' => 'Walk-in',
            'relationshipType' => 'Walk-in',
        ])->assertCreated();

        $ticketId = $created->json('tickets.0.id');

        $this->withToken($token)->postJson("/api/v1/admin/tickets/{$ticketId}/mark-used")
            ->assertOk()
            ->assertJsonPath('ticket.status', 'used')
            ->assertJsonPath('ticket.usedAt', fn ($value) => is_string($value) && $value !== '');

        $this->assertDatabaseHas('ticket_purchases', [
            'id' => $ticketId,
            'status' => 'Used',
        ]);

        $this->assertNotNull(TicketPurchase::query()->findOrFail($ticketId)->used_at);
    }

    public function test_admin_cannot_mark_unpaid_ticket_as_used(): void
    {
        $token = AdminUser::factory()->create()->createToken('admin-access')->plainTextToken;

        $created = $this->withToken($token)->postJson('/api/v1/admin/manual-sales', [
            'customerMode' => 'walk_in',
            'saleType' => 'ticket',
            'eventSlug' => 'zangi-book-launch-mulungushi-lusaka',
            'ticketType' => 'VIP',
            'buyerName' => 'Pending Buyer',
            'email' => 'pending@example.com',
            'phone' => '+260971555404',
            'quantity' => 1,
            'priceMode' => 'standard',
            'paymentMethod' => 'Cash',
            'issueStatus' => 'reserved',
            'customerType' => 'Walk-in',
            'relationshipType' => 'Walk-in',
        ])->assertCreated();

        $ticketId = $created->json('tickets.0.id');

        $this->withToken($token)->postJson("/api/v1/admin/tickets/{$ticketId}/mark-used")
            ->assertStatus(422)
            ->assertJsonValidationErrors('ticket');
    }

    public function test_admin_can_validate_ticket_codes_for_paid_and_unpaid_sales(): void
    {
        $token = AdminUser::factory()->create()->createToken('admin-access')->plainTextToken;

        $paid = $this->withToken($token)->postJson('/api/v1/admin/manual-sales', [
            'customerMode' => 'walk_in',
            'saleType' => 'ticket',
            'eventSlug' => 'zangi-book-launch-mulungushi-lusaka',
            'ticketType' => 'VIP',
            'buyerName' => 'Validated Buyer',
            'email' => 'validated@example.com',
            'phone' => '+260971555505',
            'quantity' => 1,
            'priceMode' => 'standard',
            'paymentMethod' => 'Cash',
            'issueStatus' => 'paid',
            'customerType' => 'Walk-in',
            'relationshipType' => 'Walk-in',
        ])->assertCreated();

        $reserved = $this->withToken($token)->postJson('/api/v1/admin/manual-sales', [
            'customerMode' => 'walk_in',
            'saleType' => 'ticket',
            'eventSlug' => 'zangi-book-launch-mulungushi-lusaka',
            'ticketType' => 'Standard',
            'buyerName' => 'Reserved Buyer',
            'email' => 'reserved@example.com',
            'phone' => '+260971555606',
            'quantity' => 1,
            'priceMode' => 'standard',
            'paymentMethod' => 'Cash',
            'issueStatus' => 'reserved',
            'customerType' => 'Walk-in',
            'relationshipType' => 'Walk-in',
        ])->assertCreated();

        $paidTicketCode = $paid->json('tickets.0.code');
        $reservedTicketCode = $reserved->json('tickets.0.code');

        $this->withToken($token)->postJson('/api/v1/admin/tickets/validate', [
            'ticketCode' => $paidTicketCode,
            'eventSlug' => 'zangi-book-launch-mulungushi-lusaka',
        ])
            ->assertOk()
            ->assertJsonPath('code', $paidTicketCode)
            ->assertJsonPath('state', 'valid')
            ->assertJsonPath('ticket.code', $paidTicketCode);

        $this->withToken($token)->postJson('/api/v1/admin/tickets/validate', [
            'ticketCode' => $reservedTicketCode,
            'eventSlug' => 'zangi-book-launch-mulungushi-lusaka',
        ])
            ->assertOk()
            ->assertJsonPath('code', $reservedTicketCode)
            ->assertJsonPath('state', 'invalid')
            ->assertJsonPath('message', 'This ticket is not ready for admission yet.');
    }

    public function test_admin_can_resend_reissue_and_void_paid_tickets(): void
    {
        Mail::fake();

        $token = AdminUser::factory()->create()->createToken('admin-access')->plainTextToken;

        $created = $this->withToken($token)->postJson('/api/v1/admin/manual-sales', [
            'customerMode' => 'walk_in',
            'saleType' => 'ticket',
            'eventSlug' => 'zangi-book-launch-mulungushi-lusaka',
            'ticketType' => 'VIP',
            'buyerName' => 'Workflow Buyer',
            'email' => 'workflow@example.com',
            'phone' => '+260971555707',
            'quantity' => 1,
            'priceMode' => 'standard',
            'paymentMethod' => 'Cash',
            'issueStatus' => 'paid',
            'customerType' => 'Walk-in',
            'relationshipType' => 'Walk-in',
        ])->assertCreated();

        $ticketId = $created->json('tickets.0.id');
        $originalCode = $created->json('tickets.0.code');

        $this->withToken($token)->postJson("/api/v1/admin/tickets/{$ticketId}/resend")
            ->assertOk()
            ->assertJsonPath('ticket.code', $originalCode)
            ->assertJsonPath('ticket.notes', fn ($value) => is_string($value) && str_contains($value, 'resend email sent'));

        Mail::assertSent(TicketPassMail::class, function (TicketPassMail $mail): bool {
            return $mail->hasTo('workflow@example.com')
                && $mail->ticketPurchase->email === 'workflow@example.com';
        });

        $reissued = $this->withToken($token)->postJson("/api/v1/admin/tickets/{$ticketId}/reissue")
            ->assertOk()
            ->assertJsonPath('ticket.status', 'issued')
            ->assertJsonPath('ticket.code', fn ($value) => is_string($value) && $value !== $originalCode);

        $this->withToken($token)->postJson("/api/v1/admin/tickets/{$ticketId}/void")
            ->assertOk()
            ->assertJsonPath('ticket.status', 'voided');

        $this->assertDatabaseHas('ticket_purchases', [
            'id' => $ticketId,
            'status' => 'Voided',
        ]);

        $this->assertNotNull(TicketPurchase::query()->findOrFail($ticketId)->admin_notes);
    }
}
