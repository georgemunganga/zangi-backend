<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

class CatalogService
{
    public function buyerTypes(): array
    {
        return config('zangi_catalog.buyer_types', []);
    }

    public function findBook(string $slug): ?array
    {
        return config("zangi_catalog.books.{$slug}");
    }

    public function findBookFormat(string $slug, string $formatType): ?array
    {
        return config("zangi_catalog.books.{$slug}.formats.{$formatType}");
    }

    public function findEvent(string $slug): ?array
    {
        $eventKey = $this->resolveEventKey($slug);

        return $eventKey ? config("zangi_catalog.events.{$eventKey}") : null;
    }

    public function findEventTicketType(string $slug, string $ticketTypeId): ?array
    {
        $eventKey = $this->resolveEventKey($slug);

        return $eventKey ? config("zangi_catalog.events.{$eventKey}.ticket_types.{$ticketTypeId}") : null;
    }

    public function requireBookFormat(string $slug, string $formatType): array
    {
        $book = $this->findBook($slug);
        $format = $this->findBookFormat($slug, $formatType);

        if (! $book || ! $format) {
            throw new InvalidArgumentException('The selected book or format is not available.');
        }

        return [$book, $format];
    }

    public function assertBookFormatAvailableForCurrency(string $currency, string $formatType): void
    {
        if (strtoupper($currency) === 'ZMW' && $formatType === 'digital') {
            throw new InvalidArgumentException('Digital book orders are not available for Zambia checkout.');
        }
    }

    public function requireEventTicketType(string $slug, string $ticketTypeId): array
    {
        $event = $this->findEvent($slug);
        $ticketType = $this->findEventTicketType($slug, $ticketTypeId);

        if (! $event || ! $ticketType) {
            throw new InvalidArgumentException('The selected event or ticket type is not available.');
        }

        return [$event, $ticketType];
    }

    public function resolveEventTicketOffer(
        string $slug,
        string $ticketTypeId,
        string $currency,
        ?CarbonImmutable $moment = null,
    ): array {
        [$event, $ticketType] = $this->requireEventTicketType($slug, $ticketTypeId);
        $salesConfig = $event['ticket_sales'] ?? [];
        $saleCurrency = strtoupper((string) ($salesConfig['currency'] ?? 'ZMW'));

        if (strtoupper($currency) !== $saleCurrency) {
            throw new InvalidArgumentException('Event tickets are only available in Zambian Kwacha.');
        }

        $round = $this->resolveActiveEventTicketRound($event, $moment);
        $priceStrategy = (string) ($ticketType['price_strategy'] ?? 'fixed');
        $resolvedPrice = $priceStrategy === 'rounds'
            ? (float) data_get($round, 'standard_price_zmw', 0)
            : (float) ($ticketType['price_zmw'] ?? 0);

        if ($resolvedPrice <= 0) {
            throw new InvalidArgumentException('The selected ticket price is not available right now.');
        }

        return [
            $event,
            [
                ...$ticketType,
                'price_currency' => $saleCurrency,
                'resolved_price' => round($resolvedPrice, 2),
                'pricing_round_key' => (string) data_get($round, 'key'),
                'pricing_round_label' => (string) data_get($round, 'label'),
                'pricing_round_public_label' => (string) data_get($round, 'public_label', data_get($round, 'label')),
            ],
            $round,
        ];
    }

    public function assertPaymentMethodAllowed(
        string $purchaseType,
        string $currency,
        string $paymentMethod,
        ?string $formatType = null
    ): void {
        $allowedMethods = $this->allowedPaymentMethods($purchaseType, $currency, $formatType);

        if (! in_array($paymentMethod, $allowedMethods, true)) {
            throw new InvalidArgumentException('That payment method is not allowed for this purchase.');
        }
    }

    public function allowedPaymentMethods(string $purchaseType, string $currency, ?string $formatType = null): array
    {
        $isZambian = strtoupper($currency) === 'ZMW';

        return match ($purchaseType) {
            'book-order' => $this->bookPaymentMethods($isZambian, $formatType),
            'event-ticket' => $isZambian ? ['mobile-money', 'card'] : ['card'],
            default => [],
        };
    }

    private function bookPaymentMethods(bool $isZambian, ?string $formatType): array
    {
        if (! $isZambian) {
            return ['card'];
        }

        if ($formatType === 'digital') {
            return [];
        }

        if ($formatType === 'hardcopy') {
            return ['mobile-money', 'card', 'cash-on-delivery'];
        }

        return [];
    }

    private function resolveActiveEventTicketRound(array $event, ?CarbonImmutable $moment = null): array
    {
        $salesConfig = $event['ticket_sales'] ?? [];
        $timezone = (string) ($salesConfig['timezone'] ?? ($event['timezone'] ?? 'Africa/Lusaka'));
        $rounds = array_values($salesConfig['rounds'] ?? []);

        if ($rounds === []) {
            throw new InvalidArgumentException('Ticket sales are not configured for this event.');
        }

        $current = ($moment ?: CarbonImmutable::now($timezone))->setTimezone($timezone);
        $firstStart = $this->parseEventTime((string) data_get($rounds, '0.starts_at'), $timezone);
        $lastEnd = $this->parseEventTime((string) data_get($rounds, (count($rounds) - 1).'.ends_at'), $timezone);

        if ($firstStart && $current->lt($firstStart)) {
            throw new InvalidArgumentException('Ticket sales open on '.$firstStart->format('F j, Y').'.');
        }

        foreach ($rounds as $round) {
            $startsAt = $this->parseEventTime((string) data_get($round, 'starts_at'), $timezone);
            $endsAt = $this->parseEventTime((string) data_get($round, 'ends_at'), $timezone);

            if (! $startsAt || ! $endsAt) {
                continue;
            }

            if ($current->betweenIncluded($startsAt, $endsAt)) {
                return $round;
            }
        }

        if ($lastEnd && $current->gt($lastEnd)) {
            throw new InvalidArgumentException('Ticket sales for this event have closed.');
        }

        throw new InvalidArgumentException('Ticket pricing is not configured correctly for this event.');
    }

    private function parseEventTime(string $value, string $timezone): ?CarbonImmutable
    {
        if (trim($value) === '') {
            return null;
        }

        return CarbonImmutable::parse($value, $timezone);
    }

    private function resolveEventKey(string $slug): ?string
    {
        $events = config('zangi_catalog.events', []);

        if (array_key_exists($slug, $events)) {
            return $slug;
        }

        foreach ($events as $eventKey => $event) {
            $aliases = array_map('strval', $event['aliases'] ?? []);

            if (in_array($slug, $aliases, true)) {
                return (string) $eventKey;
            }
        }

        return null;
    }
}
