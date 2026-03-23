<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\PortalUser;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookOrderConfirmationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public PortalUser $portalUser,
        public Order $order,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->order->payment_method === 'cash-on-delivery'
                ? 'We received your Zangi order'
                : 'Your Zangi order is confirmed',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.book-order-confirmation',
            with: [
                'portalUser' => $this->portalUser,
                'order' => $this->order,
                'currencySymbol' => strtoupper($this->order->currency) === 'ZMW' ? 'K ' : '$',
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
