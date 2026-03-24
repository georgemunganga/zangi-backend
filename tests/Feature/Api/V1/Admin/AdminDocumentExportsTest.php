<?php

namespace Tests\Feature\Api\V1\Admin;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\AdminUser;
use App\Models\TicketPurchase;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminDocumentExportsTest extends TestCase
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

    public function test_admin_can_open_a_ticket_pass_document(): void
    {
        Storage::fake(config('filesystems.default'));

        $token = AdminUser::factory()->create()->createToken('admin-access')->plainTextToken;

        $created = $this->withToken($token)->postJson('/api/v1/admin/manual-sales', [
            'customerMode' => 'walk_in',
            'saleType' => 'ticket',
            'eventSlug' => 'zangi-book-launch-mulungushi-lusaka',
            'ticketType' => 'VIP',
            'buyerName' => 'Document Ticket Buyer',
            'email' => 'ticket-doc@example.com',
            'phone' => '+260971555808',
            'quantity' => 1,
            'priceMode' => 'standard',
            'paymentMethod' => 'Cash',
            'issueStatus' => 'paid',
            'customerType' => 'Walk-in',
            'relationshipType' => 'Walk-in',
        ])->assertCreated();

        $ticketId = $created->json('tickets.0.id');
        $ticketCode = $created->json('tickets.0.code');

        $this->withToken($token)
            ->get("/api/v1/admin/tickets/{$ticketId}/download");

        $response = $this->withToken($token)->get("/api/v1/admin/tickets/{$ticketId}/download");

        $response
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertNotEmpty($ticketCode);

        $ticketPurchase = TicketPurchase::query()->findOrFail($ticketId);
        Storage::disk(config('filesystems.default'))->assertExists($ticketPurchase->pass_path);
        Storage::disk(config('filesystems.default'))->assertExists($ticketPurchase->qr_path);
        $this->assertStringEndsWith('.svg', $ticketPurchase->qr_path);
        $this->assertStringContainsString('<svg', Storage::disk(config('filesystems.default'))->get($ticketPurchase->qr_path));

        $pdfText = $this->extractPdfText($response->getContent());
        $normalizedPdfText = preg_replace('/\s+/', ' ', $pdfText) ?? $pdfText;

        $this->assertStringContainsString($ticketCode, $pdfText);
        $this->assertStringContainsString('Document Ticket Buyer', $normalizedPdfText);
        $this->assertStringContainsString('VIP: K500', $normalizedPdfText);
    }

    public function test_admin_can_open_an_order_invoice_document(): void
    {
        $token = AdminUser::factory()->create()->createToken('admin-access')->plainTextToken;

        $created = $this->withToken($token)->postJson('/api/v1/admin/manual-sales', [
            'customerMode' => 'walk_in',
            'saleType' => 'book',
            'bookSlug' => 'zangi-flag-of-kindness',
            'bookFormat' => 'Hardcopy',
            'buyerName' => 'Document Book Buyer',
            'email' => 'book-doc@example.com',
            'phone' => '+260971555909',
            'quantity' => 1,
            'priceMode' => 'custom',
            'customUnitPrice' => 420,
            'paymentMethod' => 'Card',
            'issueStatus' => 'reserved',
            'customerType' => 'Individual',
            'relationshipType' => 'Walk-in',
        ])->assertCreated();

        $orderId = $created->json('order.id');
        $orderReference = $created->json('order.reference');

        $this->withToken($token)
            ->get("/api/v1/admin/orders/{$orderId}/invoice")
            ->assertOk()
            ->assertHeader('content-type', 'text/html; charset=UTF-8')
            ->assertSee('Invoice')
            ->assertSee($orderReference);
    }

    public function test_admin_can_export_reports_as_csv_and_print_view(): void
    {
        $token = AdminUser::factory()->create()->createToken('admin-access')->plainTextToken;

        $this->withToken($token)
            ->get('/api/v1/admin/reports/export?period=weekly&format=csv')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->withToken($token)
            ->get('/api/v1/admin/reports/export?period=weekly&format=print')
            ->assertOk()
            ->assertHeader('content-type', 'text/html; charset=UTF-8')
            ->assertSee('Weekly Summary');
    }

    private function extractPdfText(string $pdfBinary): string
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'zangi-ticket-pass-');
        file_put_contents($temporaryPath, $pdfBinary);

        $process = new Process([
            'python',
            '-c',
            'import sys; from pypdf import PdfReader; print(PdfReader(sys.argv[1]).pages[0].extract_text())',
            $temporaryPath,
        ]);
        $process->setTimeout(20);
        $process->mustRun();

        @unlink($temporaryPath);

        return $process->getOutput();
    }
}
