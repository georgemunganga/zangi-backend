<?php

namespace Tests\Feature\Api\V1;

use App\Models\PortalUser;
use App\Models\TicketPurchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortalTicketPassDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_portal_can_download_a_generated_ticket_pass_when_assets_are_missing(): void
    {
        Storage::fake(config('filesystems.default'));

        $portalUser = PortalUser::query()->create([
            'role' => 'individual',
            'portal_mode' => 'individual',
            'group_type' => null,
            'has_individual_access' => true,
            'has_group_access' => false,
            'name' => 'Portal Ticket Buyer',
            'email' => 'portal.ticket@gmail.com',
            'phone' => '+260971111111',
            'organization_name' => null,
            'headline' => 'Portal profile',
            'notes' => [],
        ]);

        $ticketPurchase = TicketPurchase::query()->create([
            'reference' => 'TICKET-PORTAL-001',
            'portal_user_id' => $portalUser->id,
            'buyer_type' => 'individual',
            'email' => $portalUser->email,
            'phone' => $portalUser->phone,
            'organization_name' => null,
            'event_slug' => 'zangi-book-launch-mulungushi-lusaka',
            'event_title' => 'Book Launch',
            'date_label' => 'April 3, 2026',
            'time_label' => '14:00HRS',
            'location_label' => 'NIPA Conference Hall, Lusaka, Zambia',
            'ticket_type_id' => 'vip',
            'ticket_type_label' => 'VIP',
            'ticket_holder_name' => 'Portal Ticket Buyer',
            'buyer_name' => 'Portal Ticket Buyer',
            'quantity' => 1,
            'currency' => 'ZMW',
            'unit_price' => 500,
            'total' => 500,
            'status' => 'Ticket Ready',
            'ticket_code' => 'PASS-PORTAL1',
            'qr_path' => 'passes/tickets/TICKET-PORTAL-001.svg',
            'pass_path' => 'passes/tickets/TICKET-PORTAL-001.pdf',
        ]);

        $token = $portalUser->createToken('portal-access')->plainTextToken;

        $response = $this->withToken($token)->get("/api/v1/portal/tickets/{$ticketPurchase->id}/pass");

        $response
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        Storage::disk(config('filesystems.default'))->assertExists('passes/tickets/TICKET-PORTAL-001.pdf');
        Storage::disk(config('filesystems.default'))->assertExists('passes/tickets/TICKET-PORTAL-001.svg');
        $this->assertStringContainsString('<svg', Storage::disk(config('filesystems.default'))->get('passes/tickets/TICKET-PORTAL-001.svg'));
        $this->assertStringStartsWith('%PDF', Storage::disk(config('filesystems.default'))->get('passes/tickets/TICKET-PORTAL-001.pdf'));
    }
}
