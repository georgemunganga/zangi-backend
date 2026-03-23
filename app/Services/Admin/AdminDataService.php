<?php

namespace App\Services\Admin;

use Carbon\Carbon;
use App\Models\Order;
use App\Models\PortalUser;
use App\Models\PaymentIntent;
use App\Models\TicketPurchase;
use App\Models\ContactMessageReply;
use Illuminate\Support\Str;
use App\Models\ContactMessage;
use App\Services\CatalogService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class AdminDataService
{
    private const WALK_IN_EMAIL_DOMAIN = 'walkin.zangi.local';

    private ?Collection $bookOrders = null;
    private ?Collection $ticketPurchases = null;
    private ?Collection $paymentIntents = null;
    private ?Collection $contactMessageModels = null;
    private ?Collection $portalUsers = null;
    private ?Collection $ticketRecords = null;
    private ?Collection $unifiedOrderRecords = null;
    private ?Collection $customerRecords = null;
    private ?Collection $contactMessageRecords = null;
    private ?Collection $paymentRecords = null;

    public function __construct(
        private readonly CatalogService $catalogService,
    ) {
    }

    public function overview(): array
    {
        $orders = $this->unifiedOrderRecords();
        $tickets = $this->ticketRecords();
        $payments = $this->paymentRecords();
        $messages = $this->contactMessageRecords();
        $unpaidSales = $orders
            ->where('source', 'admin_manual')
            ->where('paymentStatus', 'pending')
            ->count();
        $failedPayments = $payments->where('status', 'failed')->count();
        $revenue = $orders->where('paymentStatus', 'paid')->sum('total');

        return [
            'generatedAt' => now()->toIso8601String(),
            'stats' => [
                ['label' => 'Revenue', 'value' => $this->formatCurrency($revenue), 'trend' => 'Paid purchases only'],
                ['label' => 'Tickets Sold', 'value' => (string) $tickets->sum('quantity'), 'trend' => 'Paid and issued tickets'],
                ['label' => 'Manual Sales', 'value' => (string) $orders->where('source', 'admin_manual')->count(), 'trend' => 'Office and POS issued sales'],
                ['label' => 'Orders', 'value' => (string) $orders->count(), 'trend' => 'Paid online and manual sales'],
                ['label' => 'Unpaid Sales', 'value' => (string) $unpaidSales, 'trend' => 'Manual sales awaiting payment'],
                ['label' => 'Failed Payments', 'value' => (string) $failedPayments, 'trend' => 'Verification required'],
            ],
            'recentOrders' => $orders
                ->take(3)
                ->map(fn (array $order) => "{$order['reference']} - {$order['customerName']} - {$this->formatCurrency($order['total'], $order['currency'])}")
                ->values()
                ->all(),
            'recentMessages' => $messages
                ->take(3)
                ->map(fn (array $message) => "{$message['customerName']}: {$message['preview']}")
                ->values()
                ->all(),
            'upcomingEvents' => $this->upcomingEvents(),
            'actionQueue' => $this->actionQueue($unpaidSales),
        ];
    }

    public function tickets(array $filters): array
    {
        $search = $this->normalizedString($filters['search'] ?? '');
        $event = $this->normalizedString($filters['event'] ?? ($filters['eventSlug'] ?? 'all'));
        $ticketType = $this->normalizedString($filters['ticketType'] ?? 'all');
        $status = $this->normalizedString($filters['status'] ?? 'all');
        $paymentStatus = $this->normalizedString($filters['paymentStatus'] ?? 'all');
        $source = $this->normalizedString($filters['source'] ?? 'all');

        $records = $this->ticketRecords()->filter(function (array $ticket) use ($search, $event, $ticketType, $status, $paymentStatus, $source): bool {
            if ($search !== '') {
                $haystack = Str::lower(implode(' ', [
                    $ticket['reference'],
                    $ticket['code'],
                    $ticket['holderName'],
                    $ticket['buyerName'],
                    $ticket['email'],
                    $ticket['phone'],
                ]));

                if (! str_contains($haystack, $search)) {
                    return false;
                }
            }

            if ($event !== 'all' && $ticket['eventSlug'] !== $event) {
                return false;
            }

            if ($ticketType !== 'all' && Str::lower($ticket['ticketType']) !== $ticketType) {
                return false;
            }

            if ($status !== 'all' && $ticket['status'] !== $status) {
                return false;
            }

            if ($paymentStatus !== 'all' && $ticket['paymentStatus'] !== $paymentStatus) {
                return false;
            }

            if ($source !== 'all' && $ticket['source'] !== $source) {
                return false;
            }

            return true;
        })->values();

        return $this->paginate($records, $filters);
    }

    public function ticket(int $ticketId): ?array
    {
        return $this->ticketRecords()->firstWhere('id', $ticketId);
    }

    public function orders(array $filters): array
    {
        $search = $this->normalizedString($filters['search'] ?? '');
        $type = $this->normalizedString($filters['type'] ?? 'all');
        $status = $this->normalizedString($filters['status'] ?? 'all');
        $paymentMethod = $this->normalizedString($filters['paymentMethod'] ?? 'all');
        $source = $this->normalizedString($filters['source'] ?? 'all');

        $records = $this->unifiedOrderRecords()->filter(function (array $order) use ($search, $type, $status, $paymentMethod, $source): bool {
            if ($search !== '') {
                $haystack = Str::lower(implode(' ', [
                    $order['reference'],
                    $order['customerName'],
                    $order['email'],
                    $order['phone'],
                ]));

                if (! str_contains($haystack, $search)) {
                    return false;
                }
            }

            if ($type !== 'all' && $order['type'] !== $type) {
                return false;
            }

            if ($status !== 'all' && $order['status'] !== $status) {
                return false;
            }

            if ($paymentMethod !== 'all' && $this->normalizedString($order['paymentMethod']) !== $paymentMethod) {
                return false;
            }

            if ($source !== 'all' && $order['source'] !== $source) {
                return false;
            }

            return true;
        })->values();

        return $this->paginate($records, $filters);
    }

    public function order(string $recordKey): ?array
    {
        return $this->unifiedOrderRecords()->firstWhere('id', $recordKey);
    }

    public function customers(array $filters): array
    {
        $search = $this->normalizedString($filters['search'] ?? '');

        $records = $this->customerRecords()->filter(function (array $customer) use ($search): bool {
            if ($search === '') {
                return true;
            }

            $haystack = Str::lower(implode(' ', [
                $customer['name'],
                $customer['email'],
                $customer['phone'],
                ...$customer['tags'],
            ]));

            return str_contains($haystack, $search);
        })->values();

        return $this->paginate($records, $filters);
    }

    public function customer(string $customerKey): ?array
    {
        return $this->customerRecords()->firstWhere('id', $customerKey);
    }

    public function contactMessages(array $filters): array
    {
        $search = $this->normalizedString($filters['search'] ?? '');
        $status = $this->normalizedString($filters['status'] ?? 'all');

        $records = $this->contactMessageRecords()->filter(function (array $message) use ($search, $status): bool {
            if ($search !== '') {
                $haystack = Str::lower(implode(' ', [
                    $message['subject'],
                    $message['customerName'],
                    $message['email'],
                    $message['preview'],
                ]));

                if (! str_contains($haystack, $search)) {
                    return false;
                }
            }

            if ($status !== 'all' && $message['status'] !== $status) {
                return false;
            }

            return true;
        })->values();

        return $this->paginate($records, $filters);
    }

    public function contactMessage(int $contactMessageId): ?array
    {
        return $this->contactMessageRecords()->firstWhere('id', $contactMessageId);
    }

    public function payments(array $filters): array
    {
        $search = $this->normalizedString($filters['search'] ?? '');
        $status = $this->normalizedString($filters['status'] ?? 'all');
        $method = $this->normalizedString($filters['method'] ?? 'all');
        $source = $this->normalizedString($filters['source'] ?? 'all');

        $records = $this->paymentRecords()->filter(function (array $payment) use ($search, $status, $method, $source): bool {
            if ($search !== '') {
                $haystack = Str::lower(implode(' ', [
                    $payment['reference'],
                    $payment['orderReference'],
                    $payment['customerName'],
                    $payment['email'],
                ]));

                if (! str_contains($haystack, $search)) {
                    return false;
                }
            }

            if ($status !== 'all' && $payment['status'] !== $status) {
                return false;
            }

            if ($method !== 'all' && $this->normalizedString($payment['method']) !== $method) {
                return false;
            }

            if ($source !== 'all' && $payment['source'] !== $source) {
                return false;
            }

            return true;
        })->values();

        return $this->paginate($records, $filters);
    }

    public function payment(int $paymentIntentId): ?array
    {
        return $this->paymentRecords()->firstWhere('id', $paymentIntentId);
    }

    public function reportsSummary(string $period): array
    {
        $period = in_array($period, ['daily', 'weekly', 'monthly'], true) ? $period : 'weekly';
        $windowStart = match ($period) {
            'daily' => now()->subDay(),
            'monthly' => now()->subDays(31),
            default => now()->subDays(7),
        };

        $orders = $this->unifiedOrderRecords()->filter(fn (array $order): bool => $this->recordWithinWindow($order['createdAt'], $windowStart));
        $tickets = $this->ticketRecords()->filter(fn (array $ticket): bool => $this->recordWithinWindow($ticket['issuedAt'], $windowStart));
        $messages = $this->contactMessageRecords()->filter(fn (array $message): bool => $this->recordWithinWindow($message['receivedAt'], $windowStart));
        $revenue = $orders->where('paymentStatus', 'paid')->sum('total');
        $paidPayments = $orders->where('paymentStatus', 'paid')->count();
        $unpaidSales = $orders
            ->where('source', 'admin_manual')
            ->where('paymentStatus', 'pending')
            ->count();
        $manualOrders = $orders->where('source', 'admin_manual')->count();
        $onlineOrders = $orders->where('source', 'online')->count();

        return [
            'period' => $period,
            'cards' => [
                ['label' => 'Revenue', 'value' => $this->formatCurrency($revenue), 'detail' => "{$period} revenue from paid orders"],
                ['label' => 'Orders', 'value' => (string) $orders->count(), 'detail' => "{$period} order volume"],
                ['label' => 'Tickets', 'value' => (string) $tickets->sum('quantity'), 'detail' => "{$period} tickets issued in scope"],
                ['label' => 'Support volume', 'value' => (string) $messages->count(), 'detail' => "{$period} contact messages received"],
            ],
            'splits' => [
                ['label' => 'Manual orders', 'value' => (string) $manualOrders, 'detail' => 'POS and office-issued orders'],
                ['label' => 'Online orders', 'value' => (string) $onlineOrders, 'detail' => 'Website and portal-driven orders'],
                ['label' => 'Paid payments', 'value' => (string) $paidPayments, 'detail' => 'Confirmed successful payments'],
                ['label' => 'Unpaid sales', 'value' => (string) $unpaidSales, 'detail' => 'Manual sales awaiting payment'],
            ],
            'exports' => [
                ['format' => 'CSV', 'description' => 'Orders, tickets, and payment status summary'],
                ['format' => 'Print', 'description' => 'Printable management snapshot for the selected reporting window'],
            ],
        ];
    }

    private function ticketRecords(): Collection
    {
        if ($this->ticketRecords) {
            return $this->ticketRecords;
        }

        $paymentMap = $this->ticketPaymentMap();

        return $this->ticketRecords = $this->ticketPurchases()
            ->map(function (TicketPurchase $ticketPurchase) use ($paymentMap): array {
                $payment = $paymentMap->get($ticketPurchase->id);
                $displayEmail = $this->displayEmail($ticketPurchase->email);
                $customerName = $this->resolveCustomerName(
                    $ticketPurchase->portalUser,
                    $ticketPurchase->email,
                    $ticketPurchase->buyer_name,
                    $ticketPurchase->organization_name,
                    $ticketPurchase->buyer_type,
                );

                return [
                    'id' => $ticketPurchase->id,
                    'reference' => $ticketPurchase->reference,
                    'code' => $ticketPurchase->ticket_code ?: $ticketPurchase->reference,
                    'holderName' => $ticketPurchase->ticket_holder_name ?: $customerName,
                    'buyerName' => $ticketPurchase->buyer_name ?: $customerName,
                    'customerType' => $this->customerTypeLabel(
                        $ticketPurchase->portalUser,
                        $ticketPurchase->buyer_type,
                        $ticketPurchase->source,
                    ),
                    'relationshipType' => $this->relationshipTypeLabel(
                        $ticketPurchase->portal_user_id,
                        $ticketPurchase->source,
                    ),
                    'email' => $displayEmail,
                    'phone' => $ticketPurchase->phone,
                    'eventSlug' => $ticketPurchase->event_slug,
                    'eventTitle' => $ticketPurchase->event_title,
                    'eventDateLabel' => $ticketPurchase->date_label,
                    'timeLabel' => $ticketPurchase->time_label,
                    'venue' => $ticketPurchase->location_label,
                    'ticketTypeId' => $ticketPurchase->ticket_type_id,
                    'ticketType' => $ticketPurchase->ticket_type_label,
                    'pricingRoundKey' => $ticketPurchase->pricing_round_key,
                    'pricingRoundLabel' => $ticketPurchase->pricing_round_label,
                    'status' => $this->normalizeTicketStatus($ticketPurchase->status),
                    'statusLabel' => $ticketPurchase->status,
                    'paymentStatus' => $this->normalizePaymentStatus($payment?->status),
                    'paymentStatusLabel' => $payment?->status ?: 'Pending',
                    'source' => $ticketPurchase->source ?: 'online',
                    'amount' => (float) $ticketPurchase->total,
                    'unitPrice' => (float) $ticketPurchase->unit_price,
                    'total' => (float) $ticketPurchase->total,
                    'quantity' => (int) $ticketPurchase->quantity,
                    'currency' => $ticketPurchase->currency,
                    'paymentMethod' => $this->paymentMethodLabel($payment?->payment_method),
                    'deliveryMethod' => $ticketPurchase->pass_path ? 'Pass ready' : 'Pending delivery',
                    'issuedAt' => optional($ticketPurchase->created_at)->toIso8601String(),
                    'usedAt' => optional($ticketPurchase->used_at)->toIso8601String(),
                    'notes' => $ticketPurchase->admin_notes
                        ?: ($ticketPurchase->pass_path
                            ? 'Digital ticket pass is available for delivery.'
                            : 'Ticket is waiting for payment confirmation or fulfillment.'),
                    'qrUrl' => $ticketPurchase->qr_path,
                    'passUrl' => $ticketPurchase->pass_path ? "/api/v1/portal/tickets/{$ticketPurchase->id}/pass" : null,
                    'portalUserId' => $ticketPurchase->portal_user_id,
                    'customerId' => $this->customerKey($ticketPurchase->portal_user_id, $ticketPurchase->email),
                ];
            })
            ->filter(fn (array $ticket): bool => $this->shouldExposeOperationalRecord(
                $ticket['source'],
                $ticket['paymentStatus'],
                $ticket['paymentMethod'],
            ))
            ->sortByDesc('issuedAt')
            ->values();
    }

    private function unifiedOrderRecords(): Collection
    {
        if ($this->unifiedOrderRecords) {
            return $this->unifiedOrderRecords;
        }

        $ticketPayments = $this->ticketPaymentMap();
        $bookRecords = $this->bookOrders()->map(function (Order $order): array {
            $displayEmail = $this->displayEmail($order->email);
            $downloadReady = $this->bookDownloadAvailable($order);
            $customerName = $this->resolveCustomerName(
                $order->portalUser,
                $order->email,
                $order->buyer_name,
                $order->organization_name,
                $order->buyer_type,
            );

            return [
                'id' => "book_order_{$order->id}",
                'recordType' => 'book_order',
                'resourceId' => $order->id,
                'reference' => $order->reference,
                'type' => 'book_only',
                'status' => $this->normalizeOrderStatus($order->status),
                'statusLabel' => $order->status,
                'paymentStatus' => $this->normalizePaymentStatus($order->payment_status),
                'paymentStatusLabel' => $order->payment_status,
                'paymentMethod' => $this->paymentMethodLabel($order->payment_method),
                'source' => $order->source ?: 'online',
                'customerId' => $this->customerKey($order->portal_user_id, $order->email),
                'customerName' => $customerName,
                'customerType' => $this->customerTypeLabel(
                    $order->portalUser,
                    $order->buyer_type,
                    $order->source,
                ),
                'relationshipType' => $this->relationshipTypeLabel(
                    $order->portal_user_id,
                    $order->source,
                ),
                'email' => $displayEmail,
                'phone' => $order->phone,
                'currency' => $order->currency,
                'total' => (float) $order->total,
                'bookFormat' => $order->format,
                'downloadReady' => $downloadReady,
                'deliveryKind' => $downloadReady ? 'digital_book' : 'order_detail',
                'createdAt' => optional($order->created_at)->toIso8601String(),
                'notes' => $order->admin_notes
                    ?: ($order->organization_name ? "Organization: {$order->organization_name}." : 'Book order captured through checkout.'),
                'fulfillment' => $this->bookFulfillmentSummary($order, $downloadReady),
                'lines' => [[
                    'kind' => 'book',
                    'label' => "{$order->product_title} - {$this->headline($order->format)}",
                    'quantity' => (int) $order->quantity,
                    'unitPrice' => (float) $order->unit_price,
                ]],
                'linkedTicketIds' => [],
                'portalUserId' => $order->portal_user_id,
            ];
        });

        $ticketRecords = $this->ticketPurchases()->map(function (TicketPurchase $ticketPurchase) use ($ticketPayments): array {
            $payment = $ticketPayments->get($ticketPurchase->id);
            $displayEmail = $this->displayEmail($ticketPurchase->email);
            $customerName = $this->resolveCustomerName(
                $ticketPurchase->portalUser,
                $ticketPurchase->email,
                $ticketPurchase->buyer_name,
                $ticketPurchase->organization_name,
                $ticketPurchase->buyer_type,
            );

            return [
                'id' => "ticket_purchase_{$ticketPurchase->id}",
                'recordType' => 'ticket_purchase',
                'resourceId' => $ticketPurchase->id,
                'reference' => $ticketPurchase->reference,
                'type' => 'ticket_only',
                'status' => $this->normalizeTicketOrderStatus($ticketPurchase->status, $payment?->status),
                'statusLabel' => $ticketPurchase->status,
                'paymentStatus' => $this->normalizePaymentStatus($payment?->status),
                'paymentStatusLabel' => $payment?->status ?: 'Pending',
                'paymentMethod' => $this->paymentMethodLabel($payment?->payment_method),
                'source' => $ticketPurchase->source ?: 'online',
                'customerId' => $this->customerKey($ticketPurchase->portal_user_id, $ticketPurchase->email),
                'customerName' => $customerName,
                'customerType' => $this->customerTypeLabel(
                    $ticketPurchase->portalUser,
                    $ticketPurchase->buyer_type,
                    $ticketPurchase->source,
                ),
                'relationshipType' => $this->relationshipTypeLabel(
                    $ticketPurchase->portal_user_id,
                    $ticketPurchase->source,
                ),
                'email' => $displayEmail,
                'phone' => $ticketPurchase->phone,
                'currency' => $ticketPurchase->currency,
                'total' => (float) $ticketPurchase->total,
                'bookFormat' => null,
                'pricingRoundKey' => $ticketPurchase->pricing_round_key,
                'pricingRoundLabel' => $ticketPurchase->pricing_round_label,
                'downloadReady' => false,
                'deliveryKind' => 'ticket',
                'createdAt' => optional($ticketPurchase->created_at)->toIso8601String(),
                'notes' => $ticketPurchase->admin_notes ?: 'Ticket purchase captured through event checkout.',
                'fulfillment' => $ticketPurchase->pass_path
                    ? 'Digital ticket pass is available for delivery.'
                    : 'Awaiting payment confirmation before pass delivery.',
                'lines' => [[
                    'kind' => 'ticket',
                    'label' => "{$ticketPurchase->event_title} - {$ticketPurchase->ticket_type_label}",
                    'quantity' => (int) $ticketPurchase->quantity,
                    'unitPrice' => (float) $ticketPurchase->unit_price,
                ]],
                'linkedTicketIds' => [$ticketPurchase->id],
                'portalUserId' => $ticketPurchase->portal_user_id,
            ];
        });

        return $this->unifiedOrderRecords = $bookRecords
            ->concat($ticketRecords)
            ->filter(fn (array $order): bool => $this->shouldExposeOperationalRecord(
                $order['source'],
                $order['paymentStatus'],
                $order['paymentMethod'],
            ))
            ->sortByDesc('createdAt')
            ->values();
    }

    private function customerRecords(): Collection
    {
        if ($this->customerRecords) {
            return $this->customerRecords;
        }

        $orders = $this->unifiedOrderRecords();
        $tickets = $this->ticketRecords();
        $customers = collect();
        $portalUsersByEmail = $this->portalUsers()->keyBy(fn (PortalUser $portalUser) => Str::lower($portalUser->email));

        foreach ($this->portalUsers() as $portalUser) {
            $customerKey = $this->customerKey($portalUser->id, $portalUser->email);
            $customer = $this->buildCustomerRecord(
                $customerKey,
                $portalUser->name,
                $portalUser->email,
                $portalUser->phone,
                $portalUser->notes ?: [],
                $orders->filter(fn (array $order): bool => $order['customerId'] === $customerKey),
                $tickets->filter(fn (array $ticket): bool => $ticket['customerId'] === $customerKey),
                collect($this->roleTags($portalUser)),
            );

            if ($customer) {
                $customers->push($customer);
            }
        }

        $orphanEmails = $orders
            ->pluck('email')
            ->merge($tickets->pluck('email'))
            ->filter()
            ->map(fn (string $email): string => Str::lower($email))
            ->unique()
            ->filter(fn (string $email): bool => ! $portalUsersByEmail->has($email));

        foreach ($orphanEmails as $email) {
            $customerOrders = $orders->filter(fn (array $order): bool => Str::lower($order['email']) === $email);
            $customerTickets = $tickets->filter(fn (array $ticket): bool => Str::lower($ticket['email']) === $email);
            $firstRecord = $customerOrders->first() ?: $customerTickets->first();

            $customer = $this->buildCustomerRecord(
                $this->customerKey(null, $email),
                $firstRecord['customerName'] ?? $this->fallbackNameFromEmail($email),
                $email,
                $firstRecord['phone'] ?? null,
                [],
                $customerOrders,
                $customerTickets,
                collect(),
            );

            if ($customer) {
                $customers->push($customer);
            }
        }

        return $this->customerRecords = $customers->sortByDesc('lastActivityAt')->values();
    }

    private function contactMessageRecords(): Collection
    {
        if ($this->contactMessageRecords) {
            return $this->contactMessageRecords;
        }

        return $this->contactMessageRecords = $this->contactMessageModels()
            ->map(function (ContactMessage $contactMessage): array {
                $thread = collect([
                    [
                        'id' => "contact_message_{$contactMessage->id}_customer",
                        'author' => 'customer',
                        'name' => $contactMessage->name,
                        'body' => $contactMessage->message,
                        'sentAt' => optional($contactMessage->created_at)->toIso8601String(),
                    ],
                ])->concat(
                    $contactMessage->replies->map(function (ContactMessageReply $reply): array {
                        return [
                            'id' => "contact_message_reply_{$reply->id}",
                            'author' => $reply->author_type,
                            'name' => $reply->author_name,
                            'body' => $reply->body,
                            'sentAt' => optional($reply->sent_at ?: $reply->created_at)->toIso8601String(),
                        ];
                    }),
                )->values()->all();

                return [
                    'id' => $contactMessage->id,
                    'subject' => "Support request from {$contactMessage->name}",
                    'customerName' => $contactMessage->name,
                    'email' => $contactMessage->email,
                    'phone' => null,
                    'status' => $this->normalizeContactStatus($contactMessage->status),
                    'statusLabel' => $contactMessage->status,
                    'preview' => Str::limit($contactMessage->message, 120),
                    'notes' => 'Public contact form submission.',
                    'receivedAt' => optional($contactMessage->created_at)->toIso8601String(),
                    'thread' => $thread,
                ];
            })
            ->sortByDesc('receivedAt')
            ->values();
    }

    private function paymentRecords(): Collection
    {
        if ($this->paymentRecords) {
            return $this->paymentRecords;
        }

        return $this->paymentRecords = $this->paymentIntents()
            ->map(function (PaymentIntent $paymentIntent): array {
                $purchase = $this->resolvePaymentPurchase($paymentIntent);
                $recordKey = $purchase['recordKey'];

                return [
                    'id' => $paymentIntent->id,
                    'reference' => $paymentIntent->reference,
                    'orderId' => $recordKey,
                    'orderReference' => $purchase['reference'] ?? null,
                    'customerName' => $purchase['customerName'] ?? $this->fallbackNameFromEmail($paymentIntent->email),
                    'email' => $purchase['email'] ?? $this->displayEmail($paymentIntent->email),
                    'amount' => (float) $paymentIntent->amount,
                    'currency' => $paymentIntent->currency,
                    'method' => $this->paymentMethodLabel($paymentIntent->payment_method),
                    'status' => $this->normalizePaymentStatus($paymentIntent->status),
                    'statusLabel' => $paymentIntent->status,
                    'source' => $purchase['source'] ?? data_get($paymentIntent->lenco_payload, 'source', 'online'),
                    'purchaseType' => $paymentIntent->purchase_type,
                    'createdAt' => optional($paymentIntent->created_at)->toIso8601String(),
                    'verifiedAt' => optional($paymentIntent->verified_at)->toIso8601String(),
                    'notes' => $purchase['notes']
                        ?: ($paymentIntent->verified_at
                            ? 'Payment verification completed.'
                            : 'Awaiting payment verification.'),
                ];
            })
            ->filter(fn (array $payment): bool => $this->shouldExposePaymentRecord($payment['source'], $payment['status']))
            ->sortByDesc('createdAt')
            ->values();
    }

    private function bookOrders(): Collection
    {
        return $this->bookOrders ??= Order::query()->with('portalUser')->latest()->get();
    }

    private function ticketPurchases(): Collection
    {
        return $this->ticketPurchases ??= TicketPurchase::query()->with('portalUser')->latest()->get();
    }

    private function paymentIntents(): Collection
    {
        return $this->paymentIntents ??= PaymentIntent::query()->latest()->get();
    }

    private function contactMessageModels(): Collection
    {
        return $this->contactMessageModels ??= ContactMessage::query()->with('replies')->latest()->get();
    }

    private function portalUsers(): Collection
    {
        return $this->portalUsers ??= PortalUser::query()->latest()->get();
    }

    private function ticketPaymentMap(): Collection
    {
        return $this->paymentIntents()
            ->where('purchase_type', 'event-ticket')
            ->groupBy('purchase_id')
            ->map(fn (Collection $items) => $items->sortByDesc('created_at')->first());
    }

    private function upcomingEvents(): array
    {
        return collect(config('zangi_catalog.events', []))
            ->map(function (array $event): array {
                return [
                    'slug' => $event['slug'],
                    'title' => $event['title'],
                    'dateLabel' => $event['date_label'],
                    'timeLabel' => $event['time_label'],
                    'venue' => $event['location_label'],
                    'dateSort' => $this->parseCatalogDate($event['date_label'])?->startOfDay()?->toDateString(),
                ];
            })
            ->filter(function (array $event): bool {
                if (! $event['dateSort']) {
                    return true;
                }

                return $event['dateSort'] >= now()->startOfDay()->toDateString();
            })
            ->sortBy('dateSort')
            ->map(fn (array $event): array => collect($event)->except('dateSort')->all())
            ->values()
            ->all();
    }

    private function actionQueue(int $unpaidSales): array
    {
        $items = collect();
        $unreadMessages = $this->contactMessageRecords()->where('status', 'unread')->count();
        $pendingTickets = $this->ticketRecords()->where('status', 'pending')->count();

        if ($unpaidSales > 0) {
            $items->push("Follow up on {$unpaidSales} unpaid manual sales.");
        }

        if ($unreadMessages > 0) {
            $items->push("Respond to {$unreadMessages} unread contact messages.");
        }

        if ($pendingTickets > 0) {
            $items->push("Inspect {$pendingTickets} ticket purchases still waiting for fulfillment.");
        }

        if ($items->isEmpty()) {
            $items->push('No urgent operational actions are currently outstanding.');
        }

        return $items->values()->all();
    }

    private function buildCustomerRecord(
        string $customerId,
        ?string $name,
        ?string $email,
        ?string $phone,
        array $notes,
        Collection $orders,
        Collection $tickets,
        Collection $seedTags,
    ): ?array {
        if ($orders->isEmpty() && $tickets->isEmpty() && ! $email) {
            return null;
        }

        $tags = $seedTags
            ->merge($tickets->contains(fn (array $ticket): bool => Str::lower($ticket['ticketType']) === 'vip') ? ['VIP'] : [])
            ->merge($tickets->contains(fn (array $ticket): bool => $ticket['status'] === 'used') ? ['Attended'] : [])
            ->merge(
                $orders->contains(fn (array $order): bool => $order['paymentStatus'] === 'pending')
                || $tickets->contains(fn (array $ticket): bool => $ticket['paymentStatus'] === 'pending')
                    ? ['Unpaid sale']
                    : []
            )
            ->filter()
            ->unique()
            ->values();

        $lastActivityAt = $orders
            ->pluck('createdAt')
            ->merge($tickets->pluck('issuedAt'))
            ->filter()
            ->sortDesc()
            ->first();

        return [
            'id' => $customerId,
            'name' => $name ?: $this->fallbackNameFromEmail($email),
            'customerType' => $this->preferredCustomerType($orders, $tickets),
            'relationshipType' => $this->preferredRelationshipType($orders, $tickets),
            'email' => $email,
            'phone' => $phone,
            'totalSpent' => (float) $orders->where('paymentStatus', 'paid')->sum('total'),
            'lastActivityAt' => $lastActivityAt,
            'tags' => $tags->all(),
            'notes' => array_values($notes),
            'purchaseHistory' => $orders->values()->all(),
            'attendanceHistory' => $tickets->values()->all(),
        ];
    }

    private function bookFulfillmentSummary(Order $order, ?bool $downloadReady = null): string
    {
        $downloadReady ??= $this->bookDownloadAvailable($order);

        if ($order->format === 'digital' && $downloadReady) {
            return 'Digital download is ready in the customer portal.';
        }

        if ($order->format === 'digital') {
            return 'Digital order is in preparation for portal delivery.';
        }

        return 'Hardcopy order is being fulfilled through the standard shipping timeline.';
    }

    private function bookDownloadAvailable(Order $order): bool
    {
        if ($order->format !== 'digital' || ! $order->download_ready || ! $order->download_path) {
            return false;
        }

        return Storage::disk(config('filesystems.default'))->exists($order->download_path);
    }

    private function normalizeTicketStatus(string $status): string
    {
        return match (Str::lower(trim($status))) {
            'ticket ready' => 'issued',
            'pending' => 'pending',
            'cancelled' => 'cancelled',
            'voided' => 'voided',
            'used' => 'used',
            'refunded' => 'refunded',
            default => $this->normalizedString($status) ?: 'pending',
        };
    }

    private function normalizeTicketOrderStatus(string $ticketStatus, ?string $paymentStatus): string
    {
        if ($this->normalizePaymentStatus($paymentStatus) === 'failed') {
            return 'failed';
        }

        return match ($this->normalizeTicketStatus($ticketStatus)) {
            'issued' => 'completed',
            'pending' => 'pending',
            'cancelled', 'voided', 'used', 'refunded' => $this->normalizeTicketStatus($ticketStatus),
            default => 'processing',
        };
    }

    private function normalizeOrderStatus(string $status): string
    {
        return match (Str::lower(trim($status))) {
            'received' => 'pending',
            'confirmed', 'preparing', 'processing', 'shipped' => 'processing',
            'ready to download', 'delivered' => 'completed',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
            'failed' => 'failed',
            default => $this->normalizedString($status) ?: 'pending',
        };
    }

    private function normalizePaymentStatus(?string $status): string
    {
        return match (Str::lower(trim((string) $status))) {
            'paid' => 'paid',
            'pending on delivery', 'pending' => 'pending',
            'failed' => 'failed',
            'refunded' => 'refunded',
            default => 'pending',
        };
    }

    private function normalizeContactStatus(string $status): string
    {
        return match (Str::lower(trim($status))) {
            'new' => 'unread',
            'open' => 'unread',
            default => $this->normalizedString($status) ?: 'unread',
        };
    }

    private function resolveCustomerName(
        ?PortalUser $portalUser,
        ?string $email,
        ?string $buyerName = null,
        ?string $organizationName = null,
        ?string $buyerType = null,
    ): string {
        if ($buyerName && trim($buyerName) !== '') {
            return trim($buyerName);
        }

        if ($portalUser?->name) {
            return $portalUser->name;
        }

        if ($organizationName && in_array($buyerType, ['corporate', 'wholesale'], true)) {
            return $organizationName;
        }

        return $this->fallbackNameFromEmail($email);
    }

    private function roleTags(PortalUser $portalUser): array
    {
        $tags = [];

        if ($portalUser->group_type) {
            $tags[] = $this->headline($portalUser->group_type);
        }

        return $tags;
    }

    private function paymentMethodLabel(?string $method): string
    {
        if (! $method) {
            return 'Unknown';
        }

        return $this->headline($method);
    }

    private function customerTypeLabel(
        ?PortalUser $portalUser,
        ?string $buyerType,
        ?string $source,
    ): string {
        $normalizedBuyerType = Str::lower(trim((string) $buyerType));

        return match (true) {
            $normalizedBuyerType === 'corporate' => 'Corporate',
            $normalizedBuyerType === 'wholesale' => 'Wholesale',
            ($source === 'admin_manual' && ! $portalUser?->id) => 'Walk-in',
            default => 'Individual',
        };
    }

    private function relationshipTypeLabel(?int $portalUserId, ?string $source): string
    {
        if ($portalUserId) {
            return 'Existing';
        }

        return $source === 'admin_manual' ? 'Walk-in' : 'Existing';
    }

    private function preferredCustomerType(Collection $orders, Collection $tickets): string
    {
        $priority = [
            'Individual' => 1,
            'Walk-in' => 2,
            'Wholesale' => 3,
            'Corporate' => 4,
        ];

        return $orders
            ->pluck('customerType')
            ->merge($tickets->pluck('customerType'))
            ->filter()
            ->reduce(function (?string $selected, string $candidate) use ($priority): string {
                if (! $selected) {
                    return $candidate;
                }

                return ($priority[$candidate] ?? 0) > ($priority[$selected] ?? 0) ? $candidate : $selected;
            }, null) ?? 'Individual';
    }

    private function preferredRelationshipType(Collection $orders, Collection $tickets): string
    {
        return $orders
            ->pluck('relationshipType')
            ->merge($tickets->pluck('relationshipType'))
            ->filter()
            ->contains('Walk-in')
                ? 'Walk-in'
                : 'Existing';
    }

    private function shouldExposeOperationalRecord(
        ?string $source,
        ?string $paymentStatus,
        ?string $paymentMethod = null,
    ): bool
    {
        if ($source === 'admin_manual') {
            return true;
        }

        if (
            $this->normalizedString($paymentStatus) === 'pending'
            && $this->normalizedString($paymentMethod) === 'cash_on_delivery'
        ) {
            return true;
        }

        return in_array($paymentStatus, ['paid', 'refunded'], true);
    }

    private function shouldExposePaymentRecord(?string $source, ?string $status): bool
    {
        if ($source === 'admin_manual') {
            return true;
        }

        return $status !== 'pending';
    }

    private function resolvePaymentPurchase(PaymentIntent $paymentIntent): array
    {
        if ($paymentIntent->purchase_type === 'book-order') {
            /** @var Order|null $order */
            $order = $this->bookOrders()->firstWhere('id', $paymentIntent->purchase_id);
            $customerName = $order
                ? $this->resolveCustomerName(
                    $order->portalUser,
                    $order->email,
                    $order->buyer_name,
                    $order->organization_name,
                    $order->buyer_type,
                )
                : null;

            return [
                'recordKey' => "book_order_{$paymentIntent->purchase_id}",
                'reference' => $order?->reference,
                'customerName' => $customerName,
                'email' => $this->displayEmail($order?->email ?: $paymentIntent->email),
                'source' => $order?->source ?: data_get($paymentIntent->lenco_payload, 'source', 'online'),
                'notes' => $order?->admin_notes,
            ];
        }

        /** @var TicketPurchase|null $ticketPurchase */
        $ticketPurchase = $this->ticketPurchases()->firstWhere('id', $paymentIntent->purchase_id);
        $customerName = $ticketPurchase
            ? $this->resolveCustomerName(
                $ticketPurchase->portalUser,
                $ticketPurchase->email,
                $ticketPurchase->buyer_name,
                $ticketPurchase->organization_name,
                $ticketPurchase->buyer_type,
            )
            : null;

        return [
            'recordKey' => "ticket_purchase_{$paymentIntent->purchase_id}",
            'reference' => $ticketPurchase?->reference,
            'customerName' => $customerName,
            'email' => $this->displayEmail($ticketPurchase?->email ?: $paymentIntent->email),
            'source' => $ticketPurchase?->source ?: data_get($paymentIntent->lenco_payload, 'source', 'online'),
            'notes' => $ticketPurchase?->admin_notes,
        ];
    }

    private function displayEmail(?string $email): ?string
    {
        $normalizedEmail = Str::lower(trim((string) $email));

        if ($normalizedEmail === '' || str_ends_with($normalizedEmail, '@'.self::WALK_IN_EMAIL_DOMAIN)) {
            return null;
        }

        return $normalizedEmail;
    }

    private function recordWithinWindow(?string $value, Carbon $windowStart): bool
    {
        if (! $value) {
            return false;
        }

        return Carbon::parse($value)->greaterThanOrEqualTo($windowStart);
    }

    private function formatCurrency(float|int $amount, ?string $currency = null): string
    {
        $currency = $currency ?: strtoupper((string) config('zangi_catalog.currency.site', 'ZMW'));

        return "{$currency} ".number_format((float) $amount, 2);
    }

    private function paginate(Collection $items, array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($filters['perPage'] ?? 15)));
        $total = $items->count();

        return [
            'data' => $items->forPage($page, $perPage)->values()->all(),
            'meta' => [
                'currentPage' => $page,
                'lastPage' => max(1, (int) ceil($total / $perPage)),
                'perPage' => $perPage,
                'total' => $total,
            ],
        ];
    }

    private function customerKey(?int $portalUserId, ?string $email): string
    {
        if ($portalUserId) {
            return "portal_{$portalUserId}";
        }

        $email = Str::lower(trim((string) $email));

        return 'guest_'.md5($email ?: 'unknown');
    }

    private function fallbackNameFromEmail(?string $email): string
    {
        $localPart = Str::before((string) $email, '@');
        $normalized = preg_replace('/[._-]+/', ' ', $localPart) ?: 'Guest Customer';

        return Str::title(trim($normalized));
    }

    private function headline(?string $value): string
    {
        return Str::of((string) $value)
            ->replace(['-', '_'], ' ')
            ->headline()
            ->toString();
    }

    private function normalizedString(mixed $value): string
    {
        return Str::of((string) $value)
            ->trim()
            ->lower()
            ->replace(' ', '_')
            ->toString();
    }

    private function parseCatalogDate(string $value): ?Carbon
    {
        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
