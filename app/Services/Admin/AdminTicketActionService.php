<?php

namespace App\Services\Admin;

use App\Mail\TicketPassMail;
use Carbon\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;
use App\Models\PaymentIntent;
use App\Models\TicketPurchase;
use Illuminate\Support\Facades\Mail;

class AdminTicketActionService
{
    public function validateCode(string $ticketCode, ?string $eventSlug): array
    {
        $normalizedCode = Str::upper(trim($ticketCode));
        $ticketPurchase = TicketPurchase::query()
            ->where('ticket_code', $normalizedCode)
            ->latest()
            ->first();
        $checkedAt = now()->toIso8601String();

        if (! $ticketPurchase) {
            return [
                'code' => $normalizedCode,
                'state' => 'invalid',
                'ticketId' => null,
                'checkedAt' => $checkedAt,
                'message' => 'No ticket matches this code in the current ticket set.',
            ];
        }

        $normalizedStatus = $this->normalizedStatus($ticketPurchase->status);

        if ($eventSlug && $ticketPurchase->event_slug !== $eventSlug) {
            return [
                'code' => $normalizedCode,
                'state' => 'wrong_event',
                'ticketId' => $ticketPurchase->id,
                'checkedAt' => $checkedAt,
                'message' => "Ticket belongs to {$ticketPurchase->event_title}, not the currently selected event.",
            ];
        }

        if ($normalizedStatus === 'used') {
            return [
                'code' => $normalizedCode,
                'state' => 'already_used',
                'ticketId' => $ticketPurchase->id,
                'checkedAt' => $checkedAt,
                'message' => 'This ticket has already been checked in.',
            ];
        }

        if (in_array($normalizedStatus, ['cancelled', 'voided', 'refunded'], true)) {
            return [
                'code' => $normalizedCode,
                'state' => 'cancelled',
                'ticketId' => $ticketPurchase->id,
                'checkedAt' => $checkedAt,
                'message' => 'This ticket is no longer active and cannot be admitted.',
            ];
        }

        if ($this->isExpired($ticketPurchase, $normalizedStatus)) {
            return [
                'code' => $normalizedCode,
                'state' => 'expired',
                'ticketId' => $ticketPurchase->id,
                'checkedAt' => $checkedAt,
                'message' => 'The event date has passed, so this ticket is expired.',
            ];
        }

        if ($this->normalizedPaymentStatus($ticketPurchase) !== 'paid' || $normalizedStatus !== 'ticket_ready') {
            return [
                'code' => $normalizedCode,
                'state' => 'invalid',
                'ticketId' => $ticketPurchase->id,
                'checkedAt' => $checkedAt,
                'message' => 'This ticket is not ready for admission yet.',
            ];
        }

        $holderName = $ticketPurchase->ticket_holder_name ?: $ticketPurchase->buyer_name;

        return [
            'code' => $normalizedCode,
            'state' => 'valid',
            'ticketId' => $ticketPurchase->id,
            'checkedAt' => $checkedAt,
            'message' => "{$holderName} can be admitted for {$ticketPurchase->event_title}.",
        ];
    }

    public function markUsed(TicketPurchase $ticketPurchase): TicketPurchase
    {
        $status = $this->normalizedStatus($ticketPurchase->status);
        $paymentStatus = $this->normalizedPaymentStatus($ticketPurchase);

        if ($status === 'used') {
            throw new InvalidArgumentException('This ticket has already been used.');
        }

        if (in_array($status, ['cancelled', 'voided'], true)) {
            throw new InvalidArgumentException('Cancelled or voided tickets cannot be admitted.');
        }

        if ($paymentStatus !== 'paid') {
            throw new InvalidArgumentException('Only paid tickets can be marked as used.');
        }

        if ($status !== 'ticket_ready') {
            throw new InvalidArgumentException('This ticket is not ready for admission yet.');
        }

        $ticketPurchase->forceFill([
            'status' => 'Used',
            'used_at' => now(),
        ])->save();

        return $ticketPurchase->fresh();
    }

    public function voidTicket(TicketPurchase $ticketPurchase): TicketPurchase
    {
        $status = $this->normalizedStatus($ticketPurchase->status);

        if (in_array($status, ['used', 'cancelled', 'voided', 'refunded'], true)) {
            throw new InvalidArgumentException('This ticket cannot be voided in its current state.');
        }

        $ticketPurchase->forceFill([
            'status' => 'Voided',
            'used_at' => null,
            'qr_path' => null,
            'pass_path' => null,
            'admin_notes' => $this->appendAdminNote($ticketPurchase->admin_notes, 'Ticket voided by admin action.'),
        ])->save();

        return $ticketPurchase->fresh();
    }

    public function reissue(TicketPurchase $ticketPurchase): TicketPurchase
    {
        $status = $this->normalizedStatus($ticketPurchase->status);

        if (in_array($status, ['used', 'cancelled', 'voided', 'refunded'], true)) {
            throw new InvalidArgumentException('This ticket cannot be reissued in its current state.');
        }

        if ($this->normalizedPaymentStatus($ticketPurchase) !== 'paid') {
            throw new InvalidArgumentException('Only paid tickets can be reissued.');
        }

        $newTicketCode = $this->makeReference('PASS');

        $ticketPurchase->forceFill([
            'status' => 'Ticket Ready',
            'ticket_code' => $newTicketCode,
            'qr_path' => "passes/tickets/{$newTicketCode}.svg",
            'pass_path' => "passes/tickets/{$newTicketCode}.pdf",
            'admin_notes' => $this->appendAdminNote($ticketPurchase->admin_notes, 'Ticket reissued with a fresh pass code.'),
        ])->save();

        return $ticketPurchase->fresh();
    }

    public function resend(TicketPurchase $ticketPurchase, AdminDocumentService $adminDocumentService): TicketPurchase
    {
        $status = $this->normalizedStatus($ticketPurchase->status);

        if (in_array($status, ['cancelled', 'voided', 'refunded'], true)) {
            throw new InvalidArgumentException('Only active tickets can be resent.');
        }

        if ($this->normalizedPaymentStatus($ticketPurchase) !== 'paid') {
            throw new InvalidArgumentException('Only paid tickets can be resent.');
        }

        if (! $ticketPurchase->ticket_code) {
            throw new InvalidArgumentException('This ticket is not ready to resend yet.');
        }

        if (! $this->hasDeliverableEmail($ticketPurchase->email)) {
            throw new InvalidArgumentException('This ticket does not have a deliverable customer email yet.');
        }

        $attachment = $adminDocumentService->ticketPassAttachment($ticketPurchase);

        Mail::to($ticketPurchase->email)->send(
            new TicketPassMail($ticketPurchase->fresh(), $attachment['binary'], $attachment['filename']),
        );

        $ticketPurchase->forceFill([
            'admin_notes' => $this->appendAdminNote($ticketPurchase->admin_notes, 'Ticket resend email sent by admin action.'),
        ])->save();

        return $ticketPurchase->fresh();
    }

    private function latestPaymentIntent(TicketPurchase $ticketPurchase): ?PaymentIntent
    {
        return PaymentIntent::query()
            ->where('purchase_type', 'event-ticket')
            ->where('purchase_id', $ticketPurchase->id)
            ->latest()
            ->first();
    }

    private function normalizedPaymentStatus(TicketPurchase $ticketPurchase): string
    {
        return Str::lower(trim((string) $this->latestPaymentIntent($ticketPurchase)?->status));
    }

    private function normalizedStatus(?string $status): string
    {
        return Str::of((string) $status)
            ->trim()
            ->lower()
            ->replace(' ', '_')
            ->toString();
    }

    private function hasDeliverableEmail(?string $email): bool
    {
        $normalizedEmail = Str::lower(trim((string) $email));

        if ($normalizedEmail === '') {
            return false;
        }

        return ! Str::endsWith($normalizedEmail, '@walkin.zangi.local');
    }

    private function isExpired(TicketPurchase $ticketPurchase, string $normalizedStatus): bool
    {
        if ($normalizedStatus === 'expired') {
            return true;
        }

        try {
            return Carbon::parse($ticketPurchase->date_label)->startOfDay()->lt(now()->startOfDay());
        } catch (\Throwable) {
            return false;
        }
    }

    private function appendAdminNote(?string $currentNote, string $note): string
    {
        $trimmedCurrent = trim((string) $currentNote);

        if ($trimmedCurrent === '') {
            return $note;
        }

        return "{$trimmedCurrent} | {$note}";
    }

    private function makeReference(string $prefix): string
    {
        return $prefix.'-'.Str::upper(Str::random(8));
    }
}
