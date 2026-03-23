<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessageReceivedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public ContactMessage $contactMessage,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'We received your Zangi message',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-message-received',
            with: [
                'contactMessage' => $this->contactMessage,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
