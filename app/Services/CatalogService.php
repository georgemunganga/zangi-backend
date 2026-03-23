<?php

namespace App\Services;

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
        return config("zangi_catalog.events.{$slug}");
    }

    public function findEventTicketType(string $slug, string $ticketTypeId): ?array
    {
        return config("zangi_catalog.events.{$slug}.ticket_types.{$ticketTypeId}");
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
}
