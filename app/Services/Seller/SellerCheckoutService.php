<?php

namespace App\Services\Seller;

use App\Models\PaymentIntent;
use App\Models\PortalUser;
use App\Models\Seller;
use App\Models\TicketPurchase;
use App\Services\CatalogService;
use App\Services\CheckoutService;
use App\Services\LencoService;
use App\Support\PortalProfileDefaults;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class SellerCheckoutService
{
    public const MANUAL_DEPOSIT_PHONE = '+260972931526';
    private const WALK_IN_EMAIL_DOMAIN = 'walkin.zangi.local';
    private const EVENT_URL_PREFIX = 'https://www.zangisworld.com/events';

    public function __construct(
        private readonly CatalogService $catalogService,
        private readonly CheckoutService $checkoutService,
        private readonly LencoService $lencoService,
    ) {
    }

    public function createMobileMoneyIntent(Seller $seller, array $payload): array
    {
        return DB::transaction(function () use ($seller, $payload): array {
            [$event, $ticketType] = $this->resolveTicketOffer(
                (string) ($payload['eventId'] ?? 'zangi-book-launch-mulungushi-lusaka'),
                (string) $payload['ticketTypeId'],
            );

            $created = $this->createSellerTicketSale(
                $seller,
                $event,
                $ticketType,
                $payload,
                'mobile-money',
                'Pending',
                $payload['returnPath'] ?? '/seller/checkout',
            );

            $widgetIntent = $this->lencoService->buildWidgetIntent(
                $created['paymentIntent'],
                ['mobile-money'],
            );

            return [
                ...$widgetIntent,
                'sale' => $this->serializeSale($created['ticketPurchase'], $created['paymentIntent']),
                'ticketReference' => $created['ticketPurchase']->reference,
                'share' => $this->shareContext($event),
            ];
        });
    }

    public function verifyMobileMoneyPayment(Seller $seller, string $reference): array
    {
        [$paymentIntent, $ticketPurchase] = $this->resolveSellerPaymentIntent($seller, $reference);

        if ($paymentIntent->payment_method !== 'mobile-money') {
            throw new InvalidArgumentException('Only seller mobile money payments can be verified here.');
        }

        $verification = $this->lencoService->verifyCollection($paymentIntent);

        $paymentIntent->forceFill([
            'status' => $verification['paid'] ? 'Paid' : ($verification['pending'] ? 'Pending' : 'Failed'),
            'verified_at' => $verification['paid'] ? now() : $paymentIntent->verified_at,
            'lenco_response' => $verification['lencoResponse'],
        ])->save();

        if ($verification['paid']) {
            $this->checkoutService->finalizePaymentIntent($paymentIntent);
            $ticketPurchase = $ticketPurchase->fresh();
            $paymentIntent = $paymentIntent->fresh();
        }

        return [
            'reference' => $paymentIntent->reference,
            'status' => $verification['status'],
            'paid' => $verification['paid'],
            'pending' => $verification['pending'],
            'sale' => $this->serializeSale($ticketPurchase, $paymentIntent),
            'share' => $this->shareContext([
                'slug' => $ticketPurchase->event_slug,
                'title' => $ticketPurchase->event_title,
            ]),
        ];
    }

    public function confirmManualDeposit(Seller $seller, array $payload): array
    {
        return DB::transaction(function () use ($seller, $payload): array {
            [$event, $ticketType] = $this->resolveTicketOffer(
                (string) ($payload['eventId'] ?? 'zangi-book-launch-mulungushi-lusaka'),
                (string) $payload['ticketTypeId'],
            );

            $created = $this->createSellerTicketSale(
                $seller,
                $event,
                $ticketType,
                $payload,
                'manual-deposit',
                'Paid',
                '/seller/deposit-confirmed',
                [
                    'status' => 'paid',
                    'confirmed_by' => $seller->code,
                    'deposit_phone' => self::MANUAL_DEPOSIT_PHONE,
                    'deposit_reference' => $this->normalizeOptionalString($payload['depositReference'] ?? null),
                    'deposit_note' => $this->normalizeOptionalString($payload['depositNote'] ?? null),
                ],
            );

            $this->checkoutService->finalizePaymentIntent($created['paymentIntent']);

            $ticketPurchase = $created['ticketPurchase']->fresh();
            $paymentIntent = $created['paymentIntent']->fresh();

            return [
                'sale' => $this->serializeSale($ticketPurchase, $paymentIntent),
                'message' => 'Deposit confirmed and ticket issued.',
                'share' => $this->shareContext($event),
            ];
        });
    }

    private function createSellerTicketSale(
        Seller $seller,
        array $event,
        array $ticketType,
        array $payload,
        string $paymentMethod,
        string $paymentStatus,
        string $returnPath,
        ?array $paymentResponse = null,
    ): array {
        $buyerType = in_array((string) ($payload['buyerType'] ?? 'individual'), ['individual', 'corporate'], true)
            ? (string) ($payload['buyerType'] ?? 'individual')
            : 'individual';
        $quantity = (int) ($payload['quantity'] ?? 1);
        $email = $this->normalizeOptionalString($payload['buyerEmail'] ?? null);
        $phone = trim((string) ($payload['buyerPhone'] ?? ''));
        $buyerName = $this->normalizeOptionalString($payload['buyerName'] ?? null)
            ?: ($phone !== '' ? $phone : 'Field Buyer');
        $portalUser = $email ? $this->resolvePortalUser($email, $buyerType, $buyerName, $phone) : null;
        $storedEmail = $email ? $this->normalizeEmail($email) : $this->resolveGuestEmail($phone, $buyerName);
        $unitPrice = round((float) data_get($ticketType, 'resolved_price', 0), 2);
        $total = round($unitPrice * $quantity, 2);

        $ticketPurchase = TicketPurchase::query()->create([
            'reference' => $this->makeReference('ZT'),
            'portal_user_id' => $portalUser?->id,
            'seller_id' => $seller->id,
            'seller_code' => $seller->code,
            'buyer_type' => $buyerType,
            'email' => $storedEmail,
            'phone' => $phone !== '' ? $phone : 'Pending update',
            'organization_name' => null,
            'event_slug' => $event['slug'],
            'event_title' => $event['title'],
            'date_label' => $event['date_label'],
            'time_label' => $event['time_label'],
            'location_label' => $event['location_label'],
            'ticket_type_id' => $ticketType['id'],
            'ticket_type_label' => $ticketType['label'],
            'pricing_round_key' => data_get($ticketType, 'pricing_round_key'),
            'pricing_round_label' => data_get($ticketType, 'pricing_round_label'),
            'ticket_holder_name' => $buyerName,
            'buyer_name' => $buyerName,
            'quantity' => $quantity,
            'currency' => 'ZMW',
            'unit_price' => $unitPrice,
            'total' => $total,
            'status' => 'Pending',
            'source' => 'seller_terminal',
            'synced' => true,
            'email_sent' => false,
            'ticket_code' => null,
            'qr_path' => null,
            'pass_path' => null,
            'admin_notes' => $this->normalizeOptionalString($payload['depositNote'] ?? null),
        ]);

        $paymentIntent = PaymentIntent::query()->create([
            'reference' => $this->makeReference('LEN'),
            'purchase_type' => 'event-ticket',
            'purchase_id' => $ticketPurchase->id,
            'buyer_type' => $buyerType,
            'email' => $storedEmail,
            'currency' => 'ZMW',
            'amount' => $total,
            'payment_method' => $paymentMethod,
            'status' => $paymentStatus,
            'lenco_payload' => array_filter([
                'source' => 'seller_terminal',
                'sellerId' => $seller->id,
                'sellerCode' => $seller->code,
                'eventSlug' => $event['slug'],
                'ticketTypeId' => $ticketType['id'],
                'pricingRoundKey' => data_get($ticketType, 'pricing_round_key'),
                'pricingRoundLabel' => data_get($ticketType, 'pricing_round_label'),
                'quantity' => $quantity,
                'shareUrl' => $this->eventUrl($event['slug']),
            ], static fn (mixed $value): bool => $value !== null && $value !== ''),
            'lenco_response' => $paymentResponse,
            'return_path' => $returnPath,
            'verified_at' => $paymentStatus === 'Paid' ? now() : null,
        ]);

        return compact('ticketPurchase', 'paymentIntent');
    }

    private function resolveSellerPaymentIntent(Seller $seller, string $reference): array
    {
        $paymentIntent = PaymentIntent::query()
            ->where('reference', trim($reference))
            ->where('purchase_type', 'event-ticket')
            ->first();

        if (! $paymentIntent) {
            throw new InvalidArgumentException('Seller payment reference not found.');
        }

        $ticketPurchase = TicketPurchase::query()
            ->where('id', $paymentIntent->purchase_id)
            ->where(function ($query) use ($seller): void {
                $query
                    ->where('seller_id', $seller->id)
                    ->orWhere('seller_code', $seller->code);
            })
            ->where('source', 'seller_terminal')
            ->first();

        if (! $ticketPurchase) {
            throw new InvalidArgumentException('That payment does not belong to this seller.');
        }

        return [$paymentIntent, $ticketPurchase];
    }

    private function resolveTicketOffer(string $eventId, string $ticketTypeId): array
    {
        return $this->catalogService->resolveEventTicketOffer($eventId, $ticketTypeId, 'ZMW');
    }

    private function resolvePortalUser(string $email, string $buyerType, string $buyerName, string $phone): PortalUser
    {
        $normalizedEmail = $this->normalizeEmail($email);
        $portalUser = PortalUser::query()
            ->where('email', $normalizedEmail)
            ->first();

        if ($portalUser) {
            return $portalUser;
        }

        $defaults = PortalProfileDefaults::forRole($buyerType);

        return PortalUser::query()->create([
            'role' => $buyerType,
            'portal_mode' => $buyerType === 'corporate' ? 'group' : 'individual',
            'group_type' => $buyerType === 'corporate' ? 'corporate' : null,
            'has_individual_access' => $buyerType === 'individual',
            'has_group_access' => $buyerType === 'corporate',
            'name' => $buyerName,
            'email' => $normalizedEmail,
            'phone' => $phone !== '' ? $phone : 'Pending update',
            'organization_name' => $buyerType === 'corporate' ? $buyerName : null,
            'headline' => $defaults['headline'],
            'notes' => $defaults['notes'],
        ]);
    }

    private function serializeSale(TicketPurchase $ticketPurchase, ?PaymentIntent $paymentIntent = null): array
    {
        $paymentStatus = $paymentIntent?->status
            ?: ($ticketPurchase->status === 'Ticket Ready' ? 'Paid' : 'Pending');

        return [
            'id' => $ticketPurchase->id,
            'reference' => $ticketPurchase->reference,
            'ticketType' => [
                'id' => $ticketPurchase->ticket_type_id,
                'name' => $ticketPurchase->ticket_type_label,
                'price' => round((float) $ticketPurchase->unit_price, 2),
            ],
            'quantity' => $ticketPurchase->quantity,
            'buyerPhone' => $ticketPurchase->phone,
            'buyerEmail' => $this->isGuestEmail($ticketPurchase->email) ? '' : $ticketPurchase->email,
            'buyerName' => $ticketPurchase->buyer_name,
            'paymentMethod' => $paymentIntent ? Str::headline(str_replace('-', ' ', $paymentIntent->payment_method)) : 'Unknown',
            'paymentStatus' => $paymentStatus,
            'total' => round((float) $ticketPurchase->total, 2),
            'timestamp' => $ticketPurchase->created_at?->toIso8601String(),
            'synced' => Str::lower($paymentStatus) === 'paid',
            'emailSent' => (bool) $ticketPurchase->email_sent,
            'ticketCode' => $ticketPurchase->ticket_code,
            'eventSlug' => $ticketPurchase->event_slug,
            'shareUrl' => $this->eventUrl($ticketPurchase->event_slug),
        ];
    }

    private function shareContext(array $event): array
    {
        $eventUrl = $this->eventUrl((string) $event['slug']);
        $eventTitle = (string) ($event['title'] ?? "Zangi's Flag Book Launch");

        return [
            'eventUrl' => $eventUrl,
            'depositPhone' => self::MANUAL_DEPOSIT_PHONE,
            'whatsAppText' => "Buy your ticket for {$eventTitle}: {$eventUrl}",
        ];
    }

    private function eventUrl(string $slug): string
    {
        return rtrim(self::EVENT_URL_PREFIX, '/').'/'.ltrim($slug, '/');
    }

    private function resolveGuestEmail(string $phone, string $buyerName): string
    {
        $seed = preg_replace('/[^0-9]/', '', $phone) ?: Str::slug($buyerName, '-');
        $seed = $seed !== '' ? $seed : Str::lower(Str::random(10));

        return "walkin-{$seed}@".self::WALK_IN_EMAIL_DOMAIN;
    }

    private function isGuestEmail(string $email): bool
    {
        return Str::endsWith(Str::lower($email), '@'.self::WALK_IN_EMAIL_DOMAIN);
    }

    private function normalizeEmail(string $value): string
    {
        return Str::lower(trim($value));
    }

    private function normalizeOptionalString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    private function makeReference(string $prefix): string
    {
        return $prefix.'-'.Str::upper(Str::random(8));
    }
}
