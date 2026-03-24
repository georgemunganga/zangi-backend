<?php

namespace App\Mail;

use App\Models\PortalUser;
use App\Models\TicketPurchase;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventTicketConfirmationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public PortalUser $portalUser,
        public TicketPurchase $ticketPurchase,
        public ?string $pdfBinary = null,
        public ?string $attachmentFilename = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Zangi event ticket is ready',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.event-ticket-confirmation',
            with: [
                'portalUser' => $this->portalUser,
                'ticketPurchase' => $this->ticketPurchase,
                'currencySymbol' => strtoupper($this->ticketPurchase->currency) === 'ZMW' ? 'K ' : '$',
            ],
        );
    }

    public function attachments(): array
    {
        if (! $this->pdfBinary || ! $this->attachmentFilename) {
            return [];
        }

        return [
            Attachment::fromData(fn () => $this->pdfBinary, $this->attachmentFilename)
                ->withMime('application/pdf'),
        ];
    }
}
