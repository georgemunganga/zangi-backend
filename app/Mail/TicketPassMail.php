<?php

namespace App\Mail;

use App\Models\TicketPurchase;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketPassMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public TicketPurchase $ticketPurchase,
        public string $pdfBinary,
        public string $attachmentFilename,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Zangi ticket has been sent',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket-pass',
            with: [
                'ticketPurchase' => $this->ticketPurchase,
                'recipientName' => $this->ticketPurchase->ticket_holder_name
                    ?: $this->ticketPurchase->buyer_name
                    ?: $this->ticketPurchase->email,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfBinary, $this->attachmentFilename)
                ->withMime('application/pdf'),
        ];
    }
}
