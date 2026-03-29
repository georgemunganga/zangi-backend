<?php

namespace Tests\Feature\Api\V1\Admin;

use Carbon\Carbon;
use App\Mail\OrderDeliveryMail;
use App\Mail\TicketPassMail;
use App\Models\AdminUser;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class AdminOrderActionsTest extends TestCase
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

    public function test_admin_can_update_and_confirm_a_book_order(): void
    {
        $token = AdminUser::factory()->create()->createToken('admin-access')->plainTextToken;

        $created = $this->withToken($token)->postJson('/api/v1/admin/manual-sales', [
            'customerMode' => 'walk_in',
            'saleType' => 'book',
            'bookSlug' => 'zangi-flag-of-kindness',
            'bookFormat' => 'Hardcopy',
            'buyerName' => 'Order Action Buyer',
            'email' => 'order-actions@example.com',
            'phone' => '+260971555505',
            'quantity' => 1,
            'priceMode' => 'standard',
            'paymentMethod' => 'Cash',
            'issueStatus' => 'reserved',
            'customerType' => 'Individual',
            'relationshipType' => 'Walk-in',
        ])->assertCreated();

        $recordKey = $created->json('order.id');

        $this->withToken($token)->postJson("/api/v1/admin/orders/{$recordKey}/status", [
            'status' => 'processing',
        ])
            ->assertOk()
            ->assertJsonPath('order.status', 'processing');

        $this->withToken($token)->postJson("/api/v1/admin/orders/{$recordKey}/confirm-payment")
            ->assertOk()
            ->assertJsonPath('order.paymentStatus', 'paid')
            ->assertJsonPath('order.status', 'processing');

        $this->assertDatabaseHas('orders', [
            'reference' => $created->json('order.reference'),
            'status' => 'Confirmed',
            'payment_status' => 'Paid',
        ]);
    }

    public function test_admin_can_cancel_and_refund_ticket_orders(): void
    {
        $token = AdminUser::factory()->create()->createToken('admin-access')->plainTextToken;

        $pending = $this->withToken($token)->postJson('/api/v1/admin/manual-sales', [
            'customerMode' => 'walk_in',
            'saleType' => 'ticket',
            'eventSlug' => 'zangi-book-launch-mulungushi-lusaka',
            'ticketType' => 'VIP',
            'buyerName' => 'Cancel Buyer',
            'email' => 'cancel@example.com',
            'phone' => '+260971555606',
            'quantity' => 1,
            'priceMode' => 'standard',
            'paymentMethod' => 'Cash',
            'issueStatus' => 'reserved',
            'customerType' => 'Walk-in',
            'relationshipType' => 'Walk-in',
        ])->assertCreated();

        $this->withToken($token)->postJson("/api/v1/admin/orders/{$pending->json('order.id')}/cancel")
            ->assertOk()
            ->assertJsonPath('order.status', 'cancelled');

        $paid = $this->withToken($token)->postJson('/api/v1/admin/manual-sales', [
            'customerMode' => 'walk_in',
            'saleType' => 'ticket',
            'eventSlug' => 'zangi-book-launch-mulungushi-lusaka',
            'ticketType' => 'VIP',
            'buyerName' => 'Refund Buyer',
            'email' => 'refund-order@example.com',
            'phone' => '+260971555707',
            'quantity' => 1,
            'priceMode' => 'standard',
            'paymentMethod' => 'Cash',
            'issueStatus' => 'paid',
            'customerType' => 'Walk-in',
            'relationshipType' => 'Walk-in',
        ])->assertCreated();

        $this->withToken($token)->postJson("/api/v1/admin/orders/{$paid->json('order.id')}/refund")
            ->assertOk()
            ->assertJsonPath('order.status', 'refunded')
            ->assertJsonPath('order.paymentStatus', 'refunded');

        $this->assertDatabaseHas('ticket_purchases', [
            'reference' => $paid->json('order.reference'),
            'status' => 'Refunded',
        ]);
    }

    public function test_admin_can_resend_ticket_and_book_order_deliveries(): void
    {
        $token = AdminUser::factory()->create()->createToken('admin-access')->plainTextToken;
        $disk = config('filesystems.default');

        Storage::fake($disk);
        Mail::fake();

        $digitalBook = $this->withToken($token)->postJson('/api/v1/admin/manual-sales', [
            'customerMode' => 'walk_in',
            'saleType' => 'book',
            'bookSlug' => 'zangi-flag-of-kindness',
            'bookFormat' => 'Digital',
            'buyerName' => 'Digital Buyer',
            'email' => 'digital-book@example.com',
            'phone' => '+260971777101',
            'quantity' => 1,
            'priceMode' => 'standard',
            'paymentMethod' => 'Card',
            'issueStatus' => 'paid',
            'customerType' => 'Individual',
            'relationshipType' => 'Walk-in',
        ])->assertCreated();

        $digitalPath = 'downloads/books/zangi-flag-of-kindness.pdf';
        Storage::disk($disk)->put($digitalPath, '%PDF-1.4 fake book');

        $this->withToken($token)->postJson("/api/v1/admin/orders/{$digitalBook->json('order.id')}/resend")
            ->assertOk()
            ->assertJsonPath('message', 'Digital book email sent.');

        Mail::assertSent(OrderDeliveryMail::class, function (OrderDeliveryMail $mail) {
            return $mail->hasTo('digital-book@example.com')
                && count($mail->attachments()) === 1;
        });

        $ticketSale = $this->withToken($token)->postJson('/api/v1/admin/manual-sales', [
            'customerMode' => 'walk_in',
            'saleType' => 'ticket',
            'eventSlug' => 'zangi-book-launch-mulungushi-lusaka',
            'ticketType' => 'VIP',
            'buyerName' => 'Ticket Buyer',
            'email' => 'ticket-buyer@example.com',
            'phone' => '+260971777202',
            'quantity' => 1,
            'priceMode' => 'standard',
            'paymentMethod' => 'Cash',
            'issueStatus' => 'paid',
            'customerType' => 'Individual',
            'relationshipType' => 'Walk-in',
        ])->assertCreated();

        $this->withToken($token)->postJson("/api/v1/admin/orders/{$ticketSale->json('order.id')}/resend")
            ->assertOk()
            ->assertJsonPath('message', 'Ticket email sent.');

        Mail::assertSent(TicketPassMail::class, function (TicketPassMail $mail) {
            return $mail->hasTo('ticket-buyer@example.com');
        });

        $hardcopyBook = $this->withToken($token)->postJson('/api/v1/admin/manual-sales', [
            'customerMode' => 'walk_in',
            'saleType' => 'book',
            'bookSlug' => 'zangi-flag-of-kindness',
            'bookFormat' => 'Hardcopy',
            'buyerName' => 'Hardcopy Buyer',
            'email' => 'hardcopy-book@example.com',
            'phone' => '+260971777303',
            'quantity' => 1,
            'priceMode' => 'standard',
            'paymentMethod' => 'Cash',
            'issueStatus' => 'reserved',
            'customerType' => 'Individual',
            'relationshipType' => 'Walk-in',
        ])->assertCreated();

        $this->withToken($token)->postJson("/api/v1/admin/orders/{$hardcopyBook->json('order.id')}/resend")
            ->assertOk()
            ->assertJsonPath('message', 'Order detail email sent.');

        Mail::assertSent(OrderDeliveryMail::class, function (OrderDeliveryMail $mail) {
            return $mail->hasTo('hardcopy-book@example.com')
                && count($mail->attachments()) === 0;
        });
    }

    public function test_admin_can_resend_ticket_delivery_when_public_template_copy_is_missing(): void
    {
        $token = AdminUser::factory()->create()->createToken('admin-access')->plainTextToken;
        $disk = config('filesystems.default');
        $trackedTemplate = resource_path('ticket-templates/Ticket.pdf');
        $publicTemplate = public_path('Ticket.pdf');
        $publicTemplateBackup = public_path('Ticket.pdf.admin-order-actions-backup');

        Storage::fake($disk);
        Mail::fake();

        $this->assertFileExists($trackedTemplate);

        if (is_file($publicTemplateBackup)) {
            unlink($publicTemplateBackup);
        }

        $movedPublicTemplate = false;

        if (is_file($publicTemplate)) {
            $this->assertTrue(rename($publicTemplate, $publicTemplateBackup));
            $movedPublicTemplate = true;
        }

        try {
            $ticketSale = $this->withToken($token)->postJson('/api/v1/admin/manual-sales', [
                'customerMode' => 'walk_in',
                'saleType' => 'ticket',
                'eventSlug' => 'zangi-book-launch-mulungushi-lusaka',
                'ticketType' => 'VIP',
                'buyerName' => 'Tracked Template Buyer',
                'email' => 'tracked-template@example.com',
                'phone' => '+260971888404',
                'quantity' => 1,
                'priceMode' => 'standard',
                'paymentMethod' => 'Cash',
                'issueStatus' => 'paid',
                'customerType' => 'Individual',
                'relationshipType' => 'Walk-in',
            ])->assertCreated();

            $this->withToken($token)->postJson("/api/v1/admin/orders/{$ticketSale->json('order.id')}/resend")
                ->assertOk()
                ->assertJsonPath('message', 'Ticket email sent.');

            Mail::assertSent(TicketPassMail::class, function (TicketPassMail $mail): bool {
                return $mail->hasTo('tracked-template@example.com');
            });
        } finally {
            if ($movedPublicTemplate && is_file($publicTemplateBackup) && ! is_file($publicTemplate)) {
                rename($publicTemplateBackup, $publicTemplate);
            }
        }
    }
}
