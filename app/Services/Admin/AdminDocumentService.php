<?php

namespace App\Services\Admin;

use App\Models\TicketPurchase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Support\Facades\Storage;
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

        $ticketPurchase = TicketPurchase::query()->findOrFail($ticketPurchaseId);
        $attachment = $this->ticketPassAttachment($ticketPurchase);

        return response($attachment['binary'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$attachment['filename'].'"',
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

    public function generateTicketPassPdf(array $payload): string
    {
        $normalizedCode = trim((string) ($payload['ticketCode'] ?? ''));

        if ($normalizedCode === '') {
            throw new InvalidArgumentException('A ticket code is required to generate the PDF pass.');
        }

        return $this->renderStampedTicketPdf($this->ticketTemplatePath(), $payload);
    }

    public function ticketPassAttachment(TicketPurchase $ticketPurchase): array
    {
        $payload = $this->ticketPassPayload($ticketPurchase);
        $ticketCode = $payload['ticketCode'];
        $pdfBinary = $this->generateTicketPassPdf($payload);
        $qrSvg = $this->generateTicketQrSvg((string) $payload['qrValue']);
        $passPath = $this->normalizedPassPath($ticketPurchase);
        $qrPath = $this->normalizedQrPath($ticketPurchase, $passPath);
        $disk = Storage::disk(config('filesystems.default'));

        $disk->put($passPath, $pdfBinary);
        $disk->put($qrPath, $qrSvg);

        $ticketPurchase->forceFill([
            'pass_path' => $passPath,
            'qr_path' => $qrPath,
        ])->save();

        return [
            'filename' => $this->ticketPassFilenameBase($ticketCode).'.pdf',
            'binary' => $pdfBinary,
            'path' => $passPath,
            'qrPath' => $qrPath,
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
        $candidates = [
            resource_path('ticket-templates/Ticket.pdf'),
            storage_path('app/public/Ticket.pdf'),
            public_path('Ticket.pdf'),
        ];

        foreach ($candidates as $templatePath) {
            if (is_file($templatePath)) {
                return $templatePath;
            }
        }

        throw new InvalidArgumentException('The ticket PDF template is not available.');
    }

    private function ticketPassPayload(TicketPurchase $ticketPurchase): array
    {
        $ticketCode = trim((string) ($ticketPurchase->ticket_code ?: $ticketPurchase->reference ?: "ticket-{$ticketPurchase->id}"));
        $holderName = trim((string) ($ticketPurchase->ticket_holder_name ?: $ticketPurchase->buyer_name ?: $ticketPurchase->email));
        $ticketTypeLabel = trim((string) $ticketPurchase->ticket_type_label);
        $priceLabel = $this->formatPriceLabel($ticketPurchase);

        return [
            'ticketCode' => $ticketCode,
            'holderName' => $holderName,
            'ticketTypePriceLabel' => trim($ticketTypeLabel !== '' ? "{$ticketTypeLabel}: {$priceLabel}" : $priceLabel),
            'qrValue' => implode("\n", array_filter([
                'Zangi Ticket',
                "Code: {$ticketCode}",
                $holderName !== '' ? "Name: {$holderName}" : null,
                $ticketTypeLabel !== '' ? "Type: {$ticketTypeLabel}" : null,
                "Price: {$priceLabel}",
                "Reference: {$ticketPurchase->reference}",
                $ticketPurchase->event_title !== '' ? "Event: {$ticketPurchase->event_title}" : null,
            ])),
        ];
    }

    private function formatPriceLabel(TicketPurchase $ticketPurchase): string
    {
        $amount = (float) $ticketPurchase->unit_price;
        $formattedAmount = floor($amount) === $amount
            ? number_format($amount, 0, '.', '')
            : rtrim(rtrim(number_format($amount, 2, '.', ''), '0'), '.');
        $currency = strtoupper((string) $ticketPurchase->currency);
        $symbol = $currency === 'ZMW' ? 'K' : '$';

        return "{$symbol}{$formattedAmount}";
    }

    private function normalizedPassPath(TicketPurchase $ticketPurchase): string
    {
        $currentPath = trim((string) $ticketPurchase->pass_path);

        if ($currentPath !== '') {
            return $currentPath;
        }

        return "passes/tickets/{$ticketPurchase->reference}.pdf";
    }

    private function normalizedQrPath(TicketPurchase $ticketPurchase, string $passPath): string
    {
        $currentPath = trim((string) $ticketPurchase->qr_path);

        if ($currentPath !== '') {
            return preg_replace('/\.(png|svg)$/i', '.svg', $currentPath) ?: $currentPath.'.svg';
        }

        return preg_replace('/\.pdf$/i', '.svg', $passPath) ?: "{$passPath}.svg";
    }

    private function encodedPayload(array $payload): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (! is_string($json) || $json === '') {
            throw new InvalidArgumentException('The ticket pass payload could not be encoded.');
        }

        return base64_encode($json);
    }

    private function generateTicketQrSvg(string $qrValue): string
    {
        $process = new Process([
            'python',
            base_path('scripts/admin_stamp_ticket_pdf.py'),
            'qr-svg',
            $this->encodedPayload(['qrValue' => $qrValue]),
        ], base_path());

        $process->setTimeout(20);
        $process->run();

        if (! $process->isSuccessful()) {
            $errorOutput = trim($process->getErrorOutput());

            throw new InvalidArgumentException(
                $errorOutput !== '' ? $errorOutput : 'Unable to generate the ticket QR asset.',
            );
        }

        $output = $process->getOutput();

        if ($output === '') {
            throw new InvalidArgumentException('The ticket QR output was empty.');
        }

        return $output;
    }

    private function renderStampedTicketPdf(string $templatePath, array $payload): string
    {
        $process = new Process([
            'python',
            base_path('scripts/admin_stamp_ticket_pdf.py'),
            'pdf',
            $templatePath,
            $this->encodedPayload($payload),
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
