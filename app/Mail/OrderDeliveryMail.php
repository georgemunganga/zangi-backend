<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderDeliveryMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Order $order,
        public ?array $attachmentPayload = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->attachmentPayload
                ? 'Your Zangi digital book has been sent'
                : 'Your Zangi order details have been sent',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-delivery',
            with: [
                'order' => $this->order,
                'hasAttachment' => $this->attachmentPayload !== null,
                'recipientName' => $this->order->buyer_name ?: $this->order->email,
            ],
        );
    }

    public function attachments(): array
    {
        if (! $this->attachmentPayload) {
            return [];
        }

        return [
            Attachment::fromStorageDisk(
                $this->attachmentPayload['disk'],
                $this->attachmentPayload['path'],
            )
                ->as($this->attachmentPayload['filename'])
                ->withMime($this->attachmentPayload['mime'] ?? 'application/octet-stream'),
        ];
    }
}
