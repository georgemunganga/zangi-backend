<?php

namespace Tests\Feature\Api\V1\Admin;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Order;
use App\Models\AdminUser;
use App\Models\PortalUser;
use App\Models\PaymentIntent;
use App\Models\TicketPurchase;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminReadEndpointsTest extends TestCase
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

    public function test_portal_token_cannot_access_admin_routes(): void
    {
        $portalUser = PortalUser::query()->create([
            'role' => 'individual',
            'portal_mode' => 'individual',
            'group_type' => null,
            'has_individual_access' => true,
            'has_group_access' => false,
            'name' => 'Portal User',
            'email' => 'portal@example.com',
            'phone' => '+260971000000',
            'organization_name' => null,
            'headline' => 'Portal profile',
            'notes' => [],
        ]);

        $token = $portalUser->createToken('portal-access')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/admin/overview')
            ->assertUnauthorized();
    }

    public function test_admin_can_read_overview_and_operational_lists(): void
    {
        $token = AdminUser::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password123',
        ])->createToken('admin-access')->plainTextToken;

        $portalUser = PortalUser::query()->create([
            'role' => 'individual',
            'portal_mode' => 'individual',
            'group_type' => null,
            'has_individual_access' => true,
            'has_group_access' => false,
            'name' => 'Temwani Daka',
            'email' => 'temwani@example.com',
            'phone' => '+260971111001',
            'organization_name' => null,
            'headline' => 'Portal profile',
            'notes' => ['Tracks purchases in one place.'],
        ]);

        $order = Order::query()->create([
            'reference' => 'ZG-BOOK-0001',
            'portal_user_id' => $portalUser->id,
            'buyer_type' => 'individual',
            'email' => $portalUser->email,
            'phone' => $portalUser->phone,
            'organization_name' => null,
            'product_slug' => 'zangi-flag-of-kindness',
            'product_title' => 'Zangi: The Flag of Kindness',
            'format' => 'digital',
            'quantity' => 1,
            'currency' => 'ZMW',
            'unit_price' => 299.00,
            'total' => 299.00,
            'status' => 'Ready to Download',
            'timeline' => ['Received', 'Confirmed', 'Preparing', 'Ready to Download'],
            'current_step' => 3,
            'payment_status' => 'Paid',
            'payment_method' => 'card',
            'download_ready' => true,
            'download_path' => 'downloads/books/zangi-flag-of-kindness.pdf',
        ]);

        $ticketPurchase = TicketPurchase::query()->create([
            'reference' => 'ZT-TICKET-0001',
            'portal_user_id' => $portalUser->id,
            'buyer_type' => 'individual',
            'email' => $portalUser->email,
            'phone' => $portalUser->phone,
            'organization_name' => null,
            'event_slug' => 'zangi-book-launch-mulungushi-lusaka',
            'event_title' => "Zangi's Flag Book Launch",
            'date_label' => 'May 22, 2026',
            'time_label' => '6:00 PM - 7:30 PM CAT',
            'location_label' => 'Mulungushi International Conference Centre, Lusaka',
            'ticket_type_id' => 'vip',
            'ticket_type_label' => 'VIP',
            'ticket_holder_name' => 'Temwani Daka',
            'buyer_name' => 'Temwani Daka',
            'quantity' => 1,
            'currency' => 'ZMW',
            'unit_price' => 500.00,
            'total' => 500.00,
            'status' => 'Ticket Ready',
            'ticket_code' => 'PASS-ABC123',
            'qr_path' => 'passes/tickets/ZT-TICKET-0001.png',
            'pass_path' => 'passes/tickets/ZT-TICKET-0001.pdf',
        ]);

        PaymentIntent::query()->create([
            'reference' => 'LEN-BOOK-0001',
            'purchase_type' => 'book-order',
            'purchase_id' => $order->id,
            'buyer_type' => 'individual',
            'email' => $portalUser->email,
            'currency' => 'ZMW',
            'amount' => 299.00,
            'payment_method' => 'card',
            'status' => 'Paid',
            'lenco_payload' => [],
            'lenco_response' => ['status' => 'paid'],
            'return_path' => '/portal',
            'verified_at' => now(),
        ]);

        PaymentIntent::query()->create([
            'reference' => 'LEN-TICKET-0001',
            'purchase_type' => 'event-ticket',
            'purchase_id' => $ticketPurchase->id,
            'buyer_type' => 'individual',
            'email' => $portalUser->email,
            'currency' => 'ZMW',
            'amount' => 500.00,
            'payment_method' => 'mobile-money',
            'status' => 'Paid',
            'lenco_payload' => [],
            'lenco_response' => ['status' => 'paid'],
            'return_path' => '/portal',
            'verified_at' => now(),
        ]);

        $pendingTicketPurchase = TicketPurchase::query()->create([
            'reference' => 'ZT-TICKET-0002',
            'portal_user_id' => $portalUser->id,
            'buyer_type' => 'individual',
            'email' => $portalUser->email,
            'phone' => $portalUser->phone,
            'organization_name' => null,
            'event_slug' => 'zangi-book-launch-mulungushi-lusaka',
            'event_title' => "Zangi's Flag Book Launch",
            'date_label' => 'May 22, 2026',
            'time_label' => '6:00 PM - 7:30 PM CAT',
            'location_label' => 'Mulungushi International Conference Centre, Lusaka',
            'ticket_type_id' => 'standard',
            'ticket_type_label' => 'Standard',
            'ticket_holder_name' => 'Temwani Daka',
            'buyer_name' => 'Temwani Daka',
            'quantity' => 1,
            'currency' => 'ZMW',
            'unit_price' => 250.00,
            'total' => 250.00,
            'status' => 'Pending',
        ]);

        PaymentIntent::query()->create([
            'reference' => 'LEN-TICKET-0002',
            'purchase_type' => 'event-ticket',
            'purchase_id' => $pendingTicketPurchase->id,
            'buyer_type' => 'individual',
            'email' => $portalUser->email,
            'currency' => 'ZMW',
            'amount' => 250.00,
            'payment_method' => 'mobile-money',
            'status' => 'Pending',
            'lenco_payload' => [],
            'lenco_response' => ['status' => 'pending'],
            'return_path' => '/portal',
            'verified_at' => null,
        ]);

        $cashOnDeliveryOrder = Order::query()->create([
            'reference' => 'ZG-BOOK-0002',
            'portal_user_id' => $portalUser->id,
            'buyer_type' => 'individual',
            'email' => $portalUser->email,
            'phone' => $portalUser->phone,
            'organization_name' => null,
            'product_slug' => 'zangi-flag-of-kindness',
            'product_title' => 'Zangi: The Flag of Kindness',
            'format' => 'hardcopy',
            'quantity' => 1,
            'currency' => 'ZMW',
            'unit_price' => 358.40,
            'total' => 358.40,
            'status' => 'Received',
            'timeline' => ['Received', 'Confirmed', 'Processing', 'Shipped', 'Delivered'],
            'current_step' => 0,
            'payment_status' => 'Pending on Delivery',
            'payment_method' => 'cash-on-delivery',
            'download_ready' => false,
            'download_path' => null,
        ]);

        $contactMessage = ContactMessage::query()->create([
            'name' => 'Temwani Daka',
            'email' => $portalUser->email,
            'message' => 'Please resend my event ticket.',
            'status' => 'new',
        ]);

        $this->withToken($token)
            ->getJson('/api/v1/admin/overview')
            ->assertOk()
            ->assertJsonStructure(['stats', 'recentOrders', 'recentMessages', 'upcomingEvents', 'actionQueue']);

        $this->withToken($token)
            ->getJson('/api/v1/admin/tickets')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $ticketPurchase->id)
            ->assertJsonPath('data.0.code', 'PASS-ABC123');

        $ordersResponse = $this->withToken($token)
            ->getJson('/api/v1/admin/orders')
            ->assertOk()
            ->assertJsonPath('meta.total', 3);

        $ordersResponse->assertJsonFragment([
            'id' => 'book_order_'.$cashOnDeliveryOrder->id,
            'reference' => 'ZG-BOOK-0002',
            'paymentStatus' => 'pending',
            'paymentStatusLabel' => 'Pending on Delivery',
            'paymentMethod' => 'Cash On Delivery',
        ]);

        $this->withToken($token)
            ->getJson('/api/v1/admin/orders/book_order_'.$order->id)
            ->assertOk()
            ->assertJsonPath('reference', 'ZG-BOOK-0001');

        $this->withToken($token)
            ->getJson('/api/v1/admin/orders/book_order_'.$cashOnDeliveryOrder->id)
            ->assertOk()
            ->assertJsonPath('reference', 'ZG-BOOK-0002')
            ->assertJsonPath('paymentStatus', 'pending')
            ->assertJsonPath('paymentMethod', 'Cash On Delivery');

        $this->withToken($token)
            ->getJson('/api/v1/admin/customers')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.email', $portalUser->email);

        $this->withToken($token)
            ->getJson('/api/v1/admin/contact-messages')
            ->assertOk()
            ->assertJsonPath('data.0.id', $contactMessage->id)
            ->assertJsonPath('data.0.status', 'unread');

        $this->withToken($token)
            ->getJson('/api/v1/admin/payments')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);

        $this->withToken($token)
            ->getJson('/api/v1/admin/reports/summary?period=weekly')
            ->assertOk()
            ->assertJsonPath('period', 'weekly')
            ->assertJsonStructure(['cards', 'splits', 'exports']);
    }
}
