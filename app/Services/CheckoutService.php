<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PortalUser;
use App\Models\PaymentIntent;
use App\Models\TicketPurchase;
use Illuminate\Support\Str;

class CheckoutService
{
    public function __construct(
        private readonly CurrencyService $currencyService,
    ) {
    }

    public function createPendingBookOrder(array $book, array $format, array $payload): array
    {
        $portalUser = $this->findPortalUser($payload['email']);
        $unitPrice = $this->currencyService->convertUsdToCurrency((float) $format['base_price_usd'], $payload['currency']);
        $quantity = (int) $payload['quantity'];
        $total = round($unitPrice * $quantity, 2);

        $order = Order::create([
            'reference' => $this->makeReference('ZG'),
            'portal_user_id' => $portalUser?->id,
            'buyer_type' => $payload['buyerType'],
            'email' => $payload['email'],
            'phone' => $payload['phone'],
            'organization_name' => ($payload['organizationName'] ?? null) ?: null,
            'product_slug' => $book['slug'],
            'product_title' => $book['title'],
            'format' => $format['type'],
            'quantity' => $quantity,
            'currency' => $payload['currency'],
            'unit_price' => $unitPrice,
            'total' => $total,
            'status' => 'Received',
            'timeline' => $this->orderTimeline($format['type']),
            'current_step' => 0,
            'payment_status' => 'Pending',
            'payment_method' => $payload['paymentMethod'],
            'download_ready' => false,
            'download_path' => $format['type'] === 'digital'
                ? "downloads/books/{$book['slug']}.pdf"
                : null,
        ]);

        $paymentIntent = PaymentIntent::create([
            'reference' => $this->makeReference('LEN'),
            'purchase_type' => 'book-order',
            'purchase_id' => $order->id,
            'buyer_type' => $payload['buyerType'],
            'email' => $payload['email'],
            'currency' => $payload['currency'],
            'amount' => $total,
            'payment_method' => $payload['paymentMethod'],
            'status' => 'Pending',
            'lenco_payload' => [
                'productSlug' => $book['slug'],
                'formatType' => $format['type'],
                'quantity' => $quantity,
            ],
            'return_path' => $payload['returnPath'],
        ]);

        return compact('order', 'paymentIntent', 'unitPrice', 'total');
    }

    public function createCashOnDeliveryBookOrder(array $book, array $format, array $payload): Order
    {
        $portalUser = $this->findPortalUser($payload['email']);
        $unitPrice = $this->currencyService->convertUsdToCurrency((float) $format['base_price_usd'], $payload['currency']);
        $quantity = (int) $payload['quantity'];

        return Order::create([
            'reference' => $this->makeReference('ZG'),
            'portal_user_id' => $portalUser?->id,
            'buyer_type' => $payload['buyerType'],
            'email' => $payload['email'],
            'phone' => $payload['phone'],
            'organization_name' => ($payload['organizationName'] ?? null) ?: null,
            'product_slug' => $book['slug'],
            'product_title' => $book['title'],
            'format' => $format['type'],
            'quantity' => $quantity,
            'currency' => $payload['currency'],
            'unit_price' => $unitPrice,
            'total' => round($unitPrice * $quantity, 2),
            'status' => 'Received',
            'timeline' => $this->orderTimeline($format['type']),
            'current_step' => 0,
            'payment_status' => 'Pending on Delivery',
            'payment_method' => 'cash-on-delivery',
            'download_ready' => false,
            'download_path' => null,
        ]);
    }

    public function createPendingTicketPurchase(array $event, array $ticketType, array $payload): array
    {
        $portalUser = $this->findPortalUser($payload['email']);
        $unitPrice = $this->currencyService->convertUsdToCurrency((float) $ticketType['base_price_usd'], $payload['currency']);
        $quantity = (int) $payload['quantity'];
        $total = round($unitPrice * $quantity, 2);

        $ticketPurchase = TicketPurchase::create([
            'reference' => $this->makeReference('ZT'),
            'portal_user_id' => $portalUser?->id,
            'buyer_type' => $payload['buyerType'],
            'email' => $payload['email'],
            'phone' => $payload['phone'],
            'organization_name' => ($payload['organizationName'] ?? null) ?: null,
            'event_slug' => $event['slug'],
            'event_title' => $event['title'],
            'date_label' => $event['date_label'],
            'time_label' => $event['time_label'],
            'location_label' => $event['location_label'],
            'ticket_type_id' => $ticketType['id'],
            'ticket_type_label' => $ticketType['label'],
            'ticket_holder_name' => $payload['ticketHolderName'] ?? $payload['email'],
            'buyer_name' => $payload['customerName'] ?? $payload['email'],
            'quantity' => $quantity,
            'currency' => $payload['currency'],
            'unit_price' => $unitPrice,
            'total' => $total,
            'status' => 'Pending',
        ]);

        $paymentIntent = PaymentIntent::create([
            'reference' => $this->makeReference('LEN'),
            'purchase_type' => 'event-ticket',
            'purchase_id' => $ticketPurchase->id,
            'buyer_type' => $payload['buyerType'],
            'email' => $payload['email'],
            'currency' => $payload['currency'],
            'amount' => $total,
            'payment_method' => $payload['paymentMethod'],
            'status' => 'Pending',
            'lenco_payload' => [
                'eventSlug' => $event['slug'],
                'ticketTypeId' => $ticketType['id'],
                'quantity' => $quantity,
            ],
            'return_path' => $payload['returnPath'],
        ]);

        return compact('ticketPurchase', 'paymentIntent', 'unitPrice', 'total');
    }

    public function finalizePaymentIntent(PaymentIntent $paymentIntent): array
    {
        if ($paymentIntent->purchase_type === 'book-order') {
            $order = Order::query()->findOrFail($paymentIntent->purchase_id);
            $timeline = $order->timeline ?: $this->orderTimeline($order->format);
            $isDigital = $order->format === 'digital';

            $order->forceFill([
                'payment_status' => 'Paid',
                'status' => $isDigital ? 'Ready to Download' : 'Confirmed',
                'current_step' => $isDigital ? max(count($timeline) - 1, 0) : min(1, max(count($timeline) - 1, 0)),
                'download_ready' => $isDigital,
            ])->save();

            return ['purchaseType' => 'book-order', 'purchase' => $order];
        }

        $ticketPurchase = TicketPurchase::query()->findOrFail($paymentIntent->purchase_id);
        $ticketPurchase->forceFill([
            'status' => 'Ticket Ready',
            'ticket_code' => $ticketPurchase->ticket_code ?: $this->makeReference('PASS'),
            'pass_path' => $ticketPurchase->pass_path ?: "passes/tickets/{$ticketPurchase->reference}.pdf",
            'qr_path' => $ticketPurchase->qr_path ?: "passes/tickets/{$ticketPurchase->reference}.png",
        ])->save();

        return ['purchaseType' => 'event-ticket', 'purchase' => $ticketPurchase];
    }

    private function orderTimeline(string $formatType): array
    {
        return $formatType === 'digital'
            ? ['Received', 'Confirmed', 'Preparing', 'Ready to Download']
            : ['Received', 'Confirmed', 'Processing', 'Shipped', 'Delivered'];
    }

    private function findPortalUser(string $email): ?PortalUser
    {
        return PortalUser::query()->where('email', strtolower(trim($email)))->first();
    }

    private function makeReference(string $prefix): string
    {
        return $prefix.'-'.Str::upper(Str::random(8));
    }
}
