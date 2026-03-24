<?php

namespace App\Services\Admin;

use App\Mail\OrderDeliveryMail;
use InvalidArgumentException;
use App\Models\Order;
use App\Models\PaymentIntent;
use App\Models\TicketPurchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminOrderActionService
{
    public function updateStatus(string $recordKey, string $nextStatus): string
    {
        return DB::transaction(function () use ($recordKey, $nextStatus): string {
            $record = $this->resolveRecord($recordKey);

            if ($record['type'] === 'book_order') {
                $this->updateBookOrderStatus($record['model'], $nextStatus, $record['paymentIntent']);

                return $recordKey;
            }

            $this->updateTicketOrderStatus($record['model'], $nextStatus, $record['paymentIntent']);

            return $recordKey;
        });
    }

    public function confirmPayment(string $recordKey): string
    {
        return DB::transaction(function () use ($recordKey): string {
            $record = $this->resolveRecord($recordKey);

            if ($record['paymentIntent']) {
                $record['paymentIntent']->forceFill([
                    'status' => 'Paid',
                    'verified_at' => now(),
                ])->save();
            }

            if ($record['type'] === 'book_order') {
                $this->applyBookPaymentConfirmation($record['model']);

                return $recordKey;
            }

            $this->applyTicketPaymentConfirmation($record['model']);

            return $recordKey;
        });
    }

    public function cancel(string $recordKey): string
    {
        return DB::transaction(function () use ($recordKey): string {
            $record = $this->resolveRecord($recordKey);

            if ($record['type'] === 'book_order') {
                $record['model']->forceFill([
                    'status' => 'Cancelled',
                    'download_ready' => false,
                ])->save();

                return $recordKey;
            }

            $record['model']->forceFill([
                'status' => 'Cancelled',
            ])->save();

            return $recordKey;
        });
    }

    public function refund(string $recordKey): string
    {
        return DB::transaction(function () use ($recordKey): string {
            $record = $this->resolveRecord($recordKey);

            if ($record['paymentIntent']) {
                $record['paymentIntent']->forceFill([
                    'status' => 'Refunded',
                    'verified_at' => now(),
                ])->save();
            }

            if ($record['type'] === 'book_order') {
                $record['model']->forceFill([
                    'status' => 'Refunded',
                    'payment_status' => 'Refunded',
                    'download_ready' => false,
                ])->save();

                return $recordKey;
            }

            $record['model']->forceFill([
                'status' => 'Refunded',
            ])->save();
            $this->clearTicketFulfillment($record['model']);

            return $recordKey;
        });
    }

    public function resendDelivery(
        string $recordKey,
        AdminDocumentService $adminDocumentService,
        AdminTicketActionService $adminTicketActionService,
    ): array {
        return DB::transaction(function () use ($recordKey, $adminDocumentService, $adminTicketActionService): array {
            $record = $this->resolveRecord($recordKey);

            if ($record['type'] === 'ticket_purchase') {
                $updatedTicket = $adminTicketActionService->resend($record['model'], $adminDocumentService);

                return [
                    'recordKey' => $recordKey,
                    'message' => 'Ticket email sent.',
                    'resourceId' => $updatedTicket->id,
                ];
            }

            $order = $record['model'];

            if (! $this->hasDeliverableEmail($order->email)) {
                throw new InvalidArgumentException('This order does not have a deliverable customer email yet.');
            }

            $attachmentPayload = $this->bookAttachmentPayload($order);

            Mail::to($order->email)->send(
                new OrderDeliveryMail($order->fresh(), $attachmentPayload),
            );

            $note = $attachmentPayload
                ? 'Digital book delivery email sent by admin action.'
                : 'Order detail email sent by admin action.';

            $order->forceFill([
                'admin_notes' => $this->appendAdminNote($order->admin_notes, $note),
            ])->save();

            return [
                'recordKey' => $recordKey,
                'message' => $attachmentPayload ? 'Digital book email sent.' : 'Order detail email sent.',
                'resourceId' => $order->id,
            ];
        });
    }

    private function resolveRecord(string $recordKey): array
    {
        if (str_starts_with($recordKey, 'book_order_')) {
            $orderId = (int) str_replace('book_order_', '', $recordKey);
            $order = Order::query()->findOrFail($orderId);

            return [
                'type' => 'book_order',
                'model' => $order,
                'paymentIntent' => PaymentIntent::query()
                    ->where('purchase_type', 'book-order')
                    ->where('purchase_id', $order->id)
                    ->latest()
                    ->first(),
            ];
        }

        if (str_starts_with($recordKey, 'ticket_purchase_')) {
            $ticketPurchaseId = (int) str_replace('ticket_purchase_', '', $recordKey);
            $ticketPurchase = TicketPurchase::query()->findOrFail($ticketPurchaseId);

            return [
                'type' => 'ticket_purchase',
                'model' => $ticketPurchase,
                'paymentIntent' => PaymentIntent::query()
                    ->where('purchase_type', 'event-ticket')
                    ->where('purchase_id', $ticketPurchase->id)
                    ->latest()
                    ->first(),
            ];
        }

        throw new InvalidArgumentException('The selected order record could not be resolved.');
    }

    private function updateBookOrderStatus(Order $order, string $nextStatus, ?PaymentIntent $paymentIntent): void
    {
        match ($nextStatus) {
            'pending' => $order->forceFill([
                'status' => 'Received',
                'download_ready' => false,
                'current_step' => 0,
            ])->save(),
            'processing' => $order->forceFill([
                'status' => $order->format === 'digital' ? 'Preparing' : 'Processing',
                'current_step' => $this->timelineStep($order->timeline, 2),
            ])->save(),
            'completed' => $this->markBookOrderCompleted($order),
            'cancelled' => $order->forceFill([
                'status' => 'Cancelled',
                'download_ready' => false,
            ])->save(),
            'refunded' => $this->refund("book_order_{$order->id}"),
            'failed' => $this->markBookOrderFailed($order, $paymentIntent),
            default => throw new InvalidArgumentException('This status is not supported for book orders.'),
        };
    }

    private function updateTicketOrderStatus(TicketPurchase $ticketPurchase, string $nextStatus, ?PaymentIntent $paymentIntent): void
    {
        match ($nextStatus) {
            'pending' => $ticketPurchase->forceFill([
                'status' => 'Pending',
                'used_at' => null,
            ])->save(),
            'completed' => $this->markTicketOrderCompleted($ticketPurchase),
            'cancelled' => $ticketPurchase->forceFill([
                'status' => 'Cancelled',
            ])->save(),
            'refunded' => $this->refund("ticket_purchase_{$ticketPurchase->id}"),
            'failed' => $this->markTicketOrderFailed($ticketPurchase, $paymentIntent),
            'used' => $ticketPurchase->forceFill([
                'status' => 'Used',
                'used_at' => $ticketPurchase->used_at ?: now(),
            ])->save(),
            default => throw new InvalidArgumentException('This status is not supported for ticket orders.'),
        };
    }

    private function applyBookPaymentConfirmation(Order $order): void
    {
        $order->forceFill([
            'payment_status' => 'Paid',
            'status' => $order->format === 'digital' ? 'Ready to Download' : 'Confirmed',
            'download_ready' => $order->format === 'digital',
            'current_step' => $order->format === 'digital'
                ? $this->timelineStep($order->timeline, count($order->timeline ?? []) - 1)
                : $this->timelineStep($order->timeline, 1),
        ])->save();
    }

    private function applyTicketPaymentConfirmation(TicketPurchase $ticketPurchase): void
    {
        $ticketPurchase->forceFill([
            'status' => $ticketPurchase->status === 'Used' ? 'Used' : 'Ticket Ready',
            'qr_path' => $ticketPurchase->qr_path ?: "passes/tickets/{$ticketPurchase->reference}.svg",
            'pass_path' => $ticketPurchase->pass_path ?: "passes/tickets/{$ticketPurchase->reference}.pdf",
        ])->save();
    }

    private function markBookOrderCompleted(Order $order): void
    {
        $timeline = $order->timeline ?? [];

        $order->forceFill([
            'status' => $order->format === 'digital' ? 'Ready to Download' : 'Delivered',
            'download_ready' => $order->format === 'digital',
            'current_step' => $this->timelineStep($timeline, count($timeline) - 1),
        ])->save();
    }

    private function markBookOrderFailed(Order $order, ?PaymentIntent $paymentIntent): void
    {
        if ($paymentIntent) {
            $paymentIntent->forceFill([
                'status' => 'Failed',
                'verified_at' => null,
            ])->save();
        }

        $order->forceFill([
            'status' => 'Failed',
            'payment_status' => 'Failed',
            'download_ready' => false,
        ])->save();
    }

    private function markTicketOrderCompleted(TicketPurchase $ticketPurchase): void
    {
        $ticketPurchase->forceFill([
            'status' => 'Ticket Ready',
            'qr_path' => $ticketPurchase->qr_path ?: "passes/tickets/{$ticketPurchase->reference}.svg",
            'pass_path' => $ticketPurchase->pass_path ?: "passes/tickets/{$ticketPurchase->reference}.pdf",
        ])->save();
    }

    private function markTicketOrderFailed(TicketPurchase $ticketPurchase, ?PaymentIntent $paymentIntent): void
    {
        if ($paymentIntent) {
            $paymentIntent->forceFill([
                'status' => 'Failed',
                'verified_at' => null,
            ])->save();
        }

        $ticketPurchase->forceFill([
            'status' => in_array($ticketPurchase->status, ['Used', 'Refunded', 'Cancelled'], true)
                ? $ticketPurchase->status
                : 'Voided',
            'used_at' => $ticketPurchase->status === 'Used' ? $ticketPurchase->used_at : null,
        ])->save();
        $this->clearTicketFulfillment($ticketPurchase);
    }

    private function clearTicketFulfillment(TicketPurchase $ticketPurchase): void
    {
        $ticketPurchase->forceFill([
            'qr_path' => null,
            'pass_path' => null,
        ])->save();
    }

    private function bookAttachmentPayload(Order $order): ?array
    {
        if ($order->format !== 'digital' || ! $order->download_ready || ! $order->download_path) {
            return null;
        }

        $diskName = config('filesystems.default');
        $disk = Storage::disk($diskName);

        if (! $disk->exists($order->download_path)) {
            return null;
        }

        $filename = basename($order->download_path);
        $extension = Str::lower(pathinfo($filename, PATHINFO_EXTENSION));

        return [
            'disk' => $diskName,
            'path' => $order->download_path,
            'filename' => $filename !== '' ? $filename : 'digital-book',
            'mime' => $extension === 'pdf' ? 'application/pdf' : 'application/octet-stream',
        ];
    }

    private function hasDeliverableEmail(?string $email): bool
    {
        $normalizedEmail = Str::lower(trim((string) $email));

        if ($normalizedEmail === '') {
            return false;
        }

        return ! Str::endsWith($normalizedEmail, '@walkin.zangi.local');
    }

    private function appendAdminNote(?string $currentNote, string $note): string
    {
        $trimmedCurrent = trim((string) $currentNote);

        if ($trimmedCurrent === '') {
            return $note;
        }

        return "{$trimmedCurrent} | {$note}";
    }

    private function timelineStep(array $timeline, int $default): int
    {
        if ($timeline === []) {
            return max($default, 0);
        }

        return max(0, min($default, count($timeline) - 1));
    }
}
