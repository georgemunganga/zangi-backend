<?php

namespace App\Services\Admin;

use App\Models\Order;
use App\Models\PortalUser;
use App\Models\PaymentIntent;
use App\Models\TicketPurchase;
use App\Services\CatalogService;
use App\Services\CurrencyService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AdminManualSalesService
{
    public const WALK_IN_EMAIL_DOMAIN = 'walkin.zangi.local';

    public function __construct(
        private readonly CatalogService $catalogService,
        private readonly CurrencyService $currencyService,
    ) {
    }

    public function create(array $payload): array
    {
        return DB::transaction(function () use ($payload): array {
            return $payload['saleType'] === 'book'
                ? $this->createBookSale($payload)
                : $this->createTicketSale($payload);
        });
    }

    private function createBookSale(array $payload): array
    {
        [$book, $format] = $this->resolveBookSelection((string) $payload['bookSlug'], (string) $payload['bookFormat']);
        $customer = $this->resolveCustomerContext($payload);
        $quantity = (int) $payload['quantity'];
        $unitPrice = $this->resolveUnitPrice(
            (string) $payload['priceMode'],
            $payload['customUnitPrice'] ?? null,
            (float) $format['base_price_usd'],
        );
        $total = round($unitPrice * $quantity, 2);
        $paymentStatus = $this->resolvePaymentStatus((string) $payload['issueStatus'], (string) $payload['paymentMethod']);
        $isPaid = $paymentStatus === 'Paid';
        $timeline = $this->orderTimeline((string) $format['type']);
        $order = Order::query()->create([
            'reference' => $this->makeReference('ZG'),
            'portal_user_id' => $customer['portalUserId'],
            'buyer_type' => $customer['buyerType'],
            'email' => $customer['email'],
            'phone' => $customer['phone'],
            'organization_name' => $customer['organizationName'],
            'buyer_name' => $customer['buyerName'],
            'product_slug' => $book['slug'],
            'product_title' => $book['title'],
            'format' => $format['type'],
            'quantity' => $quantity,
            'currency' => $this->currencyService->siteCurrency(),
            'unit_price' => $unitPrice,
            'total' => $total,
            'status' => $this->bookStatus((string) $payload['issueStatus'], (string) $format['type']),
            'timeline' => $timeline,
            'current_step' => $isPaid
                ? ((string) $format['type'] === 'digital' ? max(count($timeline) - 1, 0) : min(1, max(count($timeline) - 1, 0)))
                : 0,
            'payment_status' => $paymentStatus,
            'payment_method' => $this->normalizePaymentMethod((string) $payload['paymentMethod']),
            'source' => 'admin_manual',
            'download_ready' => $isPaid && (string) $format['type'] === 'digital',
            'download_path' => (string) $format['type'] === 'digital'
                ? "downloads/books/{$book['slug']}.pdf"
                : null,
            'admin_notes' => $this->normalizeOptionalString($payload['notes'] ?? null),
        ]);

        $paymentIntent = PaymentIntent::query()->create([
            'reference' => $this->makeReference('LEN'),
            'purchase_type' => 'book-order',
            'purchase_id' => $order->id,
            'buyer_type' => $customer['buyerType'],
            'email' => $customer['email'],
            'currency' => $this->currencyService->siteCurrency(),
            'amount' => $total,
            'payment_method' => $this->normalizePaymentMethod((string) $payload['paymentMethod']),
            'status' => $paymentStatus,
            'lenco_payload' => [
                'source' => 'admin_manual',
                'productSlug' => $book['slug'],
                'formatType' => $format['type'],
                'quantity' => $quantity,
            ],
            'lenco_response' => null,
            'return_path' => '/admin/manual-sales',
            'verified_at' => $isPaid ? now() : null,
        ]);

        return [
            'saleType' => 'book',
            'orderRecordKey' => "book_order_{$order->id}",
            'ticketIds' => [],
            'paymentIntentId' => $paymentIntent->id,
        ];
    }

    private function createTicketSale(array $payload): array
    {
        [$event, $ticketType] = $this->resolveTicketSelection((string) $payload['eventSlug'], (string) $payload['ticketType']);
        $customer = $this->resolveCustomerContext($payload);
        $quantity = (int) $payload['quantity'];
        $unitPrice = $this->resolveTicketUnitPrice(
            (string) $payload['priceMode'],
            $payload['customUnitPrice'] ?? null,
            $ticketType,
        );
        $total = round($unitPrice * $quantity, 2);
        $paymentStatus = $this->resolvePaymentStatus((string) $payload['issueStatus'], (string) $payload['paymentMethod']);
        $isPaid = $paymentStatus === 'Paid';
        $reference = $this->makeReference('ZT');
        $ticketPurchase = TicketPurchase::query()->create([
            'reference' => $reference,
            'portal_user_id' => $customer['portalUserId'],
            'buyer_type' => $customer['buyerType'],
            'email' => $customer['email'],
            'phone' => $customer['phone'],
            'organization_name' => $customer['organizationName'],
            'event_slug' => $event['slug'],
            'event_title' => $event['title'],
            'date_label' => $event['date_label'],
            'time_label' => $event['time_label'],
            'location_label' => $event['location_label'],
            'ticket_type_id' => $ticketType['id'],
            'ticket_type_label' => $ticketType['label'],
            'pricing_round_key' => data_get($ticketType, 'pricing_round_key'),
            'pricing_round_label' => data_get($ticketType, 'pricing_round_label'),
            'ticket_holder_name' => $customer['buyerName'],
            'buyer_name' => $customer['buyerName'],
            'quantity' => $quantity,
            'currency' => $this->currencyService->siteCurrency(),
            'unit_price' => $unitPrice,
            'total' => $total,
            'status' => $isPaid ? 'Ticket Ready' : 'Pending',
            'source' => 'admin_manual',
            'ticket_code' => $this->makeReference('PASS'),
            'qr_path' => $isPaid ? "passes/tickets/{$reference}.png" : null,
            'pass_path' => $isPaid ? "passes/tickets/{$reference}.pdf" : null,
            'admin_notes' => $this->normalizeOptionalString($payload['notes'] ?? null),
        ]);

        $paymentIntent = PaymentIntent::query()->create([
            'reference' => $this->makeReference('LEN'),
            'purchase_type' => 'event-ticket',
            'purchase_id' => $ticketPurchase->id,
            'buyer_type' => $customer['buyerType'],
            'email' => $customer['email'],
            'currency' => $this->currencyService->siteCurrency(),
            'amount' => $total,
            'payment_method' => $this->normalizePaymentMethod((string) $payload['paymentMethod']),
            'status' => $paymentStatus,
            'lenco_payload' => [
                'source' => 'admin_manual',
                'eventSlug' => $event['slug'],
                'ticketTypeId' => $ticketType['id'],
                'pricingRoundKey' => data_get($ticketType, 'pricing_round_key'),
                'pricingRoundLabel' => data_get($ticketType, 'pricing_round_label'),
                'quantity' => $quantity,
            ],
            'lenco_response' => null,
            'return_path' => '/admin/manual-sales',
            'verified_at' => $isPaid ? now() : null,
        ]);

        return [
            'saleType' => 'ticket',
            'orderRecordKey' => "ticket_purchase_{$ticketPurchase->id}",
            'ticketIds' => [$ticketPurchase->id],
            'paymentIntentId' => $paymentIntent->id,
        ];
    }

    private function resolveCustomerContext(array $payload): array
    {
        $buyerType = $this->normalizeBuyerType($payload['customerType'] ?? null);
        $buyerName = trim((string) $payload['buyerName']);
        $portalUser = $this->resolvePortalUser(
            (string) ($payload['existingCustomerId'] ?? ''),
            $payload['email'] ?? null,
        );
        $phone = $this->normalizePhone($payload['phone'] ?? null);
        $organizationName = in_array($buyerType, ['corporate', 'wholesale'], true) ? $buyerName : null;

        if ($portalUser) {
            return [
                'portalUserId' => $portalUser->id,
                'buyerType' => $this->normalizeBuyerType($portalUser->group_type ?: $portalUser->role),
                'buyerName' => $buyerName !== '' ? $buyerName : $portalUser->name,
                'email' => $this->normalizeEmail((string) $portalUser->email),
                'phone' => $phone ?: $portalUser->phone,
                'organizationName' => $organizationName ?: $portalUser->organization_name,
            ];
        }

        return [
            'portalUserId' => null,
            'buyerType' => $buyerType,
            'buyerName' => $buyerName,
            'email' => $this->resolveGuestEmail($payload['email'] ?? null, $phone, $buyerName),
            'phone' => $phone,
            'organizationName' => $organizationName,
        ];
    }

    private function resolvePortalUser(string $existingCustomerId, mixed $email): ?PortalUser
    {
        $existingCustomerId = trim($existingCustomerId);

        if (Str::startsWith($existingCustomerId, 'portal_')) {
            $portalUserId = (int) Str::after($existingCustomerId, 'portal_');

            if ($portalUserId > 0) {
                return PortalUser::query()->find($portalUserId);
            }
        }

        $normalizedEmail = $this->normalizeOptionalString($email);

        if (! $normalizedEmail) {
            return null;
        }

        return PortalUser::query()->where('email', $this->normalizeEmail($normalizedEmail))->first();
    }

    private function resolveBookSelection(string $bookSlug, string $bookFormat): array
    {
        $book = $this->catalogService->findBook($bookSlug);

        if (! $book) {
            throw new \InvalidArgumentException('The selected book is not available.');
        }

        foreach ($book['formats'] as $format) {
            if ($this->matchesCatalogValue($bookFormat, $format['type'], $format['label'])) {
                return [$book, $format];
            }
        }

        throw new \InvalidArgumentException('The selected book format is not available.');
    }

    private function resolveTicketSelection(string $eventSlug, string $ticketType): array
    {
        $event = $this->catalogService->findEvent($eventSlug);

        if (! $event) {
            throw new \InvalidArgumentException('The selected event is not available.');
        }

        foreach ($event['ticket_types'] as $candidate) {
            if ($this->matchesCatalogValue($ticketType, $candidate['id'], $candidate['label'])) {
                [$resolvedEvent, $resolvedTicketType] = $this->catalogService->resolveEventTicketOffer(
                    $eventSlug,
                    (string) $candidate['id'],
                    $this->currencyService->siteCurrency(),
                );

                return [$resolvedEvent, $resolvedTicketType];
            }
        }

        throw new \InvalidArgumentException('The selected ticket type is not available.');
    }

    private function resolveUnitPrice(string $priceMode, mixed $customUnitPrice, float $basePriceUsd): float
    {
        if ($priceMode === 'custom') {
            return round((float) $customUnitPrice, 2);
        }

        return $this->currencyService->convertUsdToCurrency($basePriceUsd, $this->currencyService->siteCurrency());
    }

    private function resolveTicketUnitPrice(string $priceMode, mixed $customUnitPrice, array $ticketType): float
    {
        if ($priceMode === 'custom') {
            return round((float) $customUnitPrice, 2);
        }

        if (array_key_exists('resolved_price', $ticketType)) {
            return round((float) $ticketType['resolved_price'], 2);
        }

        return $this->currencyService->convertUsdToCurrency(
            (float) ($ticketType['base_price_usd'] ?? 0),
            $this->currencyService->siteCurrency(),
        );
    }

    private function resolvePaymentStatus(string $issueStatus, string $paymentMethod): string
    {
        if ($issueStatus === 'paid' || $paymentMethod === 'Complimentary') {
            return 'Paid';
        }

        return 'Pending';
    }

    private function bookStatus(string $issueStatus, string $formatType): string
    {
        if ($issueStatus !== 'paid') {
            return 'Received';
        }

        return $formatType === 'digital' ? 'Ready to Download' : 'Confirmed';
    }

    private function orderTimeline(string $formatType): array
    {
        return $formatType === 'digital'
            ? ['Received', 'Confirmed', 'Preparing', 'Ready to Download']
            : ['Received', 'Confirmed', 'Processing', 'Shipped', 'Delivered'];
    }

    private function normalizeBuyerType(mixed $value): string
    {
        return match (Str::lower(trim((string) $value))) {
            'corporate' => 'corporate',
            'wholesale' => 'wholesale',
            default => 'individual',
        };
    }

    private function normalizePaymentMethod(string $value): string
    {
        return match (trim($value)) {
            'Cash' => 'cash',
            'Mobile Money' => 'mobile-money',
            'Complimentary' => 'complimentary',
            default => 'card',
        };
    }

    private function resolveGuestEmail(mixed $email, string $phone, string $buyerName): string
    {
        $normalizedEmail = $this->normalizeOptionalString($email);

        if ($normalizedEmail) {
            return $this->normalizeEmail($normalizedEmail);
        }

        $seed = $phone !== '' ? preg_replace('/[^0-9]/', '', $phone) : Str::slug($buyerName, '-');
        $seed = $seed !== '' ? $seed : Str::lower(Str::random(10));

        return "walkin-{$seed}@".self::WALK_IN_EMAIL_DOMAIN;
    }

    private function normalizeEmail(string $value): string
    {
        return Str::lower(trim($value));
    }

    private function normalizePhone(mixed $value): string
    {
        $phone = trim((string) $value);

        return $phone !== '' ? $phone : 'Pending update';
    }

    private function normalizeOptionalString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    private function matchesCatalogValue(string $input, string $id, string $label): bool
    {
        $normalizedInput = Str::of($input)->trim()->lower()->replace(' ', '-')->toString();
        $normalizedId = Str::of($id)->trim()->lower()->replace(' ', '-')->toString();
        $normalizedLabel = Str::of($label)->trim()->lower()->replace(' ', '-')->toString();

        return $normalizedInput === $normalizedId || $normalizedInput === $normalizedLabel;
    }

    private function makeReference(string $prefix): string
    {
        return $prefix.'-'.Str::upper(Str::random(8));
    }
}
