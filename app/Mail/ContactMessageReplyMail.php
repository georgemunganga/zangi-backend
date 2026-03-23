<?php

namespace App\Mail;

use App\Models\ContactMessage;
use App\Models\ContactMessageReply;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessageReplyMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public ContactMessage $contactMessage,
        public ContactMessageReply $reply,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reply from Zangi support',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-message-reply',
            with: [
                'contactMessage' => $this->contactMessage,
                'reply' => $this->reply,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
