<?php

namespace App\Services\Admin;

use App\Models\Order;
use App\Models\PaymentIntent;
use App\Models\TicketPurchase;
use Illuminate\Support\Facades\DB;

class AdminPaymentActionService
{
    public function __construct(
        private readonly AdminOrderActionService $adminOrderActionService,
    ) {
    }

    public function reconcile(PaymentIntent $paymentIntent): PaymentIntent
    {
        DB::transaction(function () use ($paymentIntent): void {
            $this->adminOrderActionService->confirmPayment($this->recordKey($paymentIntent));
        });

        return $paymentIntent->fresh();
    }

    public function refund(PaymentIntent $paymentIntent): PaymentIntent
    {
        DB::transaction(function () use ($paymentIntent): void {
            $this->adminOrderActionService->refund($this->recordKey($paymentIntent));
        });

        return $paymentIntent->fresh();
    }

    public function markFailed(PaymentIntent $paymentIntent): PaymentIntent
    {
        DB::transaction(function () use ($paymentIntent): void {
            $this->adminOrderActionService->updateStatus($this->recordKey($paymentIntent), 'failed');
        });

        return $paymentIntent->fresh();
    }

    public function attachNote(PaymentIntent $paymentIntent, string $note): PaymentIntent
    {
        DB::transaction(function () use ($paymentIntent, $note): void {
            if ($paymentIntent->purchase_type === 'book-order') {
                $order = Order::query()->findOrFail($paymentIntent->purchase_id);
                $order->forceFill([
                    'admin_notes' => $this->appendNote($order->admin_notes, $note),
                ])->save();

                return;
            }

            $ticketPurchase = TicketPurchase::query()->findOrFail($paymentIntent->purchase_id);
            $ticketPurchase->forceFill([
                'admin_notes' => $this->appendNote($ticketPurchase->admin_notes, $note),
            ])->save();
        });

        return $paymentIntent->fresh();
    }

    private function recordKey(PaymentIntent $paymentIntent): string
    {
        return $paymentIntent->purchase_type === 'book-order'
            ? "book_order_{$paymentIntent->purchase_id}"
            : "ticket_purchase_{$paymentIntent->purchase_id}";
    }

    private function appendNote(?string $currentNote, string $note): string
    {
        $trimmedCurrent = trim((string) $currentNote);
        $trimmedNote = trim($note);

        if ($trimmedCurrent === '') {
            return $trimmedNote;
        }

        return "{$trimmedCurrent} | {$trimmedNote}";
    }
}
