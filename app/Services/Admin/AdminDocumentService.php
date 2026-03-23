<?php

namespace App\Services\Admin;

use App\Models\TicketPurchase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\Process\Process;

class AdminDocumentService
{
    public function downloadTicketPass(int $ticketPurchaseId, AdminDataService $adminDataService)
    {
        $ticket = $adminDataService->ticket($ticketPurchaseId);

        abort_unless($ticket, 404);

        if (in_array($ticket['status'], ['cancelled', 'voided', 'refunded', 'pending'], true)) {
            throw new InvalidArgumentException('This ticket is not in a state that can be downloaded.');
        }

        $ticketCode = (string) ($ticket['code'] ?: $ticket['reference'] ?: "ticket-{$ticketPurchaseId}");
        $filenameBase = $this->ticketPassFilenameBase($ticketCode);
        $pdfBinary = $this->generateTicketPassPdf($ticketCode);

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filenameBase.'.pdf"',
        ]);
    }

    public function openOrderDocument(string $recordKey, AdminDataService $adminDataService)
    {
        $order = $adminDataService->order($recordKey);

        abort_unless($order, 404);

        $documentKind = in_array($order['paymentStatus'], ['paid', 'refunded'], true) ? 'receipt' : 'invoice';
        $filename = $this->slugFilename("{$documentKind}-{$order['reference']}").'.html';
        $html = view('admin.documents.order-document', [
            'documentKind' => $documentKind,
            'generatedAt' => now()->toIso8601String(),
            'order' => $order,
        ])->render();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function exportReport(string $period, string $format, AdminDataService $adminDataService)
    {
        $report = $adminDataService->reportsSummary($period);
        $generatedAt = now()->toIso8601String();

        if ($format === 'csv') {
            $filename = 'admin-report-'.$report['period'].'-'.now()->format('Ymd-His').'.csv';

            return response()->streamDownload(function () use ($generatedAt, $report): void {
                $handle = fopen('php://output', 'w');

                fputcsv($handle, ['Admin Report Summary']);
                fputcsv($handle, ['Period', $report['period']]);
                fputcsv($handle, ['Generated At', $generatedAt]);
                fputcsv($handle, []);
                fputcsv($handle, ['Cards']);
                fputcsv($handle, ['Label', 'Value', 'Detail']);

                foreach ($report['cards'] as $card) {
                    fputcsv($handle, [$card['label'], $card['value'], $card['detail']]);
                }

                fputcsv($handle, []);
                fputcsv($handle, ['Splits']);
                fputcsv($handle, ['Label', 'Value', 'Detail']);

                foreach ($report['splits'] as $split) {
                    fputcsv($handle, [$split['label'], $split['value'], $split['detail']]);
                }

                fclose($handle);
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        if ($format === 'print') {
            $filename = 'admin-report-'.$report['period'].'-'.now()->format('Ymd-His').'.html';
            $html = view('admin.documents.report-export', [
                'generatedAt' => $generatedAt,
                'report' => $report,
            ])->render();

            return response($html, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ]);
        }

        throw new InvalidArgumentException('Unsupported report export format.');
    }

    public function generateTicketPassPdf(string $ticketCode): string
    {
        $normalizedCode = trim($ticketCode);

        if ($normalizedCode === '') {
            throw new InvalidArgumentException('A ticket code is required to generate the PDF pass.');
        }

        return $this->renderStampedTicketPdf($this->ticketTemplatePath(), $normalizedCode);
    }

    public function ticketPassAttachment(TicketPurchase $ticketPurchase): array
    {
        $ticketCode = trim((string) ($ticketPurchase->ticket_code ?: $ticketPurchase->reference ?: "ticket-{$ticketPurchase->id}"));

        return [
            'filename' => $this->ticketPassFilenameBase($ticketCode).'.pdf',
            'binary' => $this->generateTicketPassPdf($ticketCode),
        ];
    }

    public function ticketPassFilenameBase(string $value): string
    {
        return $this->slugFilename($value);
    }

    private function slugFilename(string $value): string
    {
        return Str::slug($value) ?: 'download';
    }

    private function ticketTemplatePath(): string
    {
        $templatePath = storage_path('app/public/Zangi.pdf');

        if (! is_file($templatePath)) {
            throw new InvalidArgumentException('The ticket PDF template is not available.');
        }

        return $templatePath;
    }

    private function renderStampedTicketPdf(string $templatePath, string $ticketCode): string
    {
        $process = new Process([
            'python',
            base_path('scripts/admin_stamp_ticket_pdf.py'),
            $templatePath,
            $ticketCode,
        ], base_path());

        $process->setTimeout(20);
        $process->run();

        if (! $process->isSuccessful()) {
            $errorOutput = trim($process->getErrorOutput());

            throw new InvalidArgumentException(
                $errorOutput !== '' ? $errorOutput : 'Unable to generate the stamped ticket PDF.',
            );
        }

        $output = $process->getOutput();

        if ($output === '') {
            throw new InvalidArgumentException('The stamped ticket PDF output was empty.');
        }

        return $output;
    }
}
