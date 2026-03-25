<?php

namespace App\Services\Seller;

use App\Models\Seller;
use App\Models\TicketPurchase;
use App\Services\CatalogService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class SellerService
{
    public function __construct(
        private readonly CatalogService $catalogService,
    ) {
    }

    public function getDashboard(Seller $seller, ?string $date = null): array
    {
        $targetDate = $date ? CarbonImmutable::parse($date) : CarbonImmutable::now();
        $dateStr = $targetDate->format('Y-m-d');

        $sales = $this->getSellerSales($seller);

        $todaySales = $sales->filter(function (array $sale) use ($dateStr): bool {
            return str_starts_with($sale['created_at'], $dateStr);
        });

        $pendingSync = $sales->filter(fn (array $sale) => ! $sale['synced']);

        $eventConfig = $this->catalogService->findEvent('zangi-book-launch-mulungushi-lusaka');
        $currentRound = $this->getCurrentRound($eventConfig);

        return [
            'generatedAt' => now()->toIso8601String(),
            'today' => [
                'salesCount' => $todaySales->count(),
                'salesTotal' => $todaySales->sum('total'),
                'currency' => 'ZMW',
            ],
            'pendingSync' => [
                'count' => $pendingSync->count(),
                'total' => $pendingSync->sum('total'),
            ],
            'currentRound' => [
                'key' => $currentRound['key'],
                'label' => $currentRound['label'],
                'priceZmw' => $currentRound['standard_price_zmw'],
            ],
            'event' => [
                'id' => 'evt_001',
                'name' => $eventConfig['title'],
                'date' => $eventConfig['start_date'],
            ],
        ];
    }

    public function getRecentSales(Seller $seller, int $limit = 5): array
    {
        $sales = $this->getSellerSales($seller)
            ->sortByDesc('created_at')
            ->take($limit)
            ->values()
            ->all();

        return [
            'sales' => array_map(fn (array $sale) => $this->serializeSale($sale), $sales),
        ];
    }

    public function getSales(Seller $seller, array $filters = []): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = max(1, min(100, (int) ($filters['limit'] ?? 20)));
        $status = $filters['status'] ?? 'all';
        $dateFrom = $filters['dateFrom'] ?? null;
        $dateTo = $filters['dateTo'] ?? null;

        $sales = $this->getSellerSales($seller);

        if ($status !== 'all') {
            $sales = $sales->filter(fn (array $sale) => ($status === 'synced') === (bool) $sale['synced']);
        }

        if ($dateFrom) {
            $sales = $sales->filter(fn (array $sale) => $sale['created_at'] >= $dateFrom);
        }

        if ($dateTo) {
            $sales = $sales->filter(fn (array $sale) => $sale['created_at'] <= $dateTo);
        }

        $total = $sales->count();
        $totalPages = (int) ceil($total / $limit);
        $offset = ($page - 1) * $limit;

        $paginatedSales = $sales
            ->sortByDesc('created_at')
            ->skip($offset)
            ->take($limit)
            ->values()
            ->all();

        return [
            'sales' => array_map(fn (array $sale) => $this->serializeSale($sale), $paginatedSales),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => $totalPages,
            ],
        ];
    }

    public function getSale(Seller $seller, int $saleId): ?array
    {
        $sales = $this->getSellerSales($seller);
        $sale = $sales->firstWhere('id', $saleId);

        if (! $sale) {
            return null;
        }

        return $this->serializeSale($sale);
    }

    public function createSale(Seller $seller, array $data): array
    {
        $eventSlug = $data['eventId'] ?? 'zangi-book-launch-mulungushi-lusaka';
        $ticketTypeId = $data['ticketTypeId'];
        $quantity = (int) ($data['quantity'] ?? 1);

        $eventConfig = $this->catalogService->findEvent($eventSlug);
        $ticketTypeConfig = $this->catalogService->findEventTicketType($eventSlug, $ticketTypeId);

        if (! $eventConfig || ! $ticketTypeConfig) {
            throw new \InvalidArgumentException('Invalid event or ticket type.');
        }

        $round = $this->getCurrentRound($eventConfig);
        $unitPrice = $ticketTypeConfig['price_strategy'] === 'rounds'
            ? $round['standard_price_zmw']
            : ($ticketTypeConfig['price_zmw'] ?? 0);

        $total = $unitPrice * $quantity;

        $ticketPurchase = TicketPurchase::create([
            'reference' => $this->generateReference(),
            'seller_id' => $seller->id,
            'seller_code' => $seller->code,
            'buyer_type' => 'individual',
            'email' => $data['buyerEmail'] ?? '',
            'phone' => $data['buyerPhone'] ?? '',
            'organization_name' => null,
            'event_slug' => $eventSlug,
            'event_title' => $eventConfig['title'],
            'date_label' => $eventConfig['date_label'],
            'time_label' => $eventConfig['time_label'],
            'location_label' => $eventConfig['location_label'],
            'ticket_type_id' => $ticketTypeId,
            'ticket_type_label' => $ticketTypeConfig['label'],
            'pricing_round_key' => $round['key'],
            'pricing_round_label' => $round['label'],
            'ticket_holder_name' => $data['buyerName'] ?? null,
            'buyer_name' => $data['buyerName'] ?? '',
            'quantity' => $quantity,
            'currency' => 'ZMW',
            'unit_price' => $unitPrice,
            'total' => $total,
            'status' => 'Ticket Ready',
            'source' => 'seller_terminal',
            'ticket_code' => null,
            'qr_path' => null,
            'pass_path' => null,
            'admin_notes' => null,
        ]);

        return $this->serializeSale([
            'id' => $ticketPurchase->id,
            'reference' => $ticketPurchase->reference,
            'seller_id' => $ticketPurchase->seller_id,
            'seller_code' => $ticketPurchase->seller_code,
            'ticket_type_id' => $ticketPurchase->ticket_type_id,
            'ticket_type_label' => $ticketPurchase->ticket_type_label,
            'pricing_round_key' => $ticketPurchase->pricing_round_key,
            'pricing_round_label' => $ticketPurchase->pricing_round_label,
            'quantity' => $ticketPurchase->quantity,
            'buyer_phone' => $ticketPurchase->phone,
            'buyer_email' => $ticketPurchase->email,
            'buyer_name' => $ticketPurchase->buyer_name,
            'payment_method' => $data['paymentMethod'] ?? 'Cash',
            'total' => $ticketPurchase->total,
            'synced' => true,
            'email_sent' => false,
            'ticket_code' => $ticketPurchase->ticket_code,
            'created_at' => $ticketPurchase->created_at->toIso8601String(),
        ]);
    }

    public function bulkSyncSales(Seller $seller, array $salesData): array
    {
        $synced = [];
        $failed = [];
        $results = [];

        foreach ($salesData as $saleData) {
            try {
                $saleData['sellerId'] = $seller->id;
                $saleData['sellerCode'] = $seller->code;
                $result = $this->createSale($seller, $saleData);
                $synced[] = $saleData['id'] ?? 'unknown';
                $results[] = [
                    'id' => $saleData['id'] ?? 'unknown',
                    'status' => 'success',
                    'ticketCode' => $result['ticketCode'] ?? null,
                ];
            } catch (\Exception $e) {
                $failed[] = $saleData['id'] ?? 'unknown';
                $results[] = [
                    'id' => $saleData['id'] ?? 'unknown',
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'synced' => $synced,
            'failed' => $failed,
            'results' => $results,
        ];
    }

    public function markSaleSynced(Seller $seller, int $saleId): bool
    {
        $ticketPurchase = TicketPurchase::query()
            ->where('id', $saleId)
            ->where('seller_id', $seller->id)
            ->first();

        if (! $ticketPurchase) {
            return false;
        }

        $ticketPurchase->update([
            'synced' => true,
        ]);

        return true;
    }

    public function getTicketTypes(?string $eventId = null): array
    {
        $eventSlug = $eventId ?? 'zangi-book-launch-mulungushi-lusaka';
        $eventConfig = $this->catalogService->findEvent($eventSlug);

        if (! $eventConfig) {
            return ['ticketTypes' => [], 'currentRound' => null];
        }

        $currentRound = $this->getCurrentRound($eventConfig);
        $ticketTypesConfig = $eventConfig['ticket_types'] ?? [];

        $ticketTypes = [];
        foreach ($ticketTypesConfig as $typeConfig) {
            $priceStrategy = $typeConfig['price_strategy'] ?? 'fixed';
            $price = $priceStrategy === 'rounds'
                ? ($currentRound['standard_price_zmw'] ?? 0)
                : ($typeConfig['price_zmw'] ?? 0);

            $ticketTypes[] = [
                'id' => $typeConfig['id'],
                'name' => $typeConfig['label'],
                'label' => $priceStrategy === 'rounds'
                    ? "{$currentRound['public_label']} – K{$price}"
                    : $typeConfig['label'],
                'price' => $price,
                'currency' => 'ZMW',
                'available' => true,
                'maxQuantity' => 50,
            ];
        }

        return [
            'ticketTypes' => $ticketTypes,
            'currentRound' => [
                'key' => $currentRound['key'],
                'label' => $currentRound['label'],
                'publicLabel' => $currentRound['public_label'],
                'startsAt' => $currentRound['starts_at'],
                'endsAt' => $currentRound['ends_at'],
            ],
        ];
    }

    public function getCurrentRoundInfo(): array
    {
        $eventConfig = $this->catalogService->findEvent('zangi-book-launch-mulungushi-lusaka');
        $round = $this->getCurrentRound($eventConfig);

        return [
            'key' => $round['key'],
            'label' => $round['label'],
            'publicLabel' => $round['public_label'],
            'startsAt' => $round['starts_at'],
            'endsAt' => $round['ends_at'],
            'priceZmw' => $round['standard_price_zmw'],
        ];
    }

    public function getEventDetails(string $eventId): ?array
    {
        $eventConfig = $this->catalogService->findEvent($eventId);

        if (! $eventConfig) {
            return null;
        }

        return [
            'id' => 'evt_001',
            'name' => $eventConfig['title'],
            'slug' => $eventConfig['slug'],
            'description' => "A live family book launch at NIPA in Lusaka",
            'startDate' => $eventConfig['start_date'],
            'startTime' => '14:00:00',
            'endTime' => '16:30:00',
            'timezone' => $eventConfig['timezone'],
            'venue' => [
                'name' => 'NIPA Conference Hall',
                'address' => 'Lusaka, Zambia',
                'coordinates' => ['lat' => -15.3875, 'lng' => 28.3228],
            ],
            'ticketSalesOpen' => true,
            'salesOpensAt' => $eventConfig['ticket_sales']['sales_start_at'],
            'salesClosesAt' => $eventConfig['ticket_sales']['sales_end_at'],
            'image' => '/images/event-banner.png',
            'status' => $eventConfig['status'] ?? 'upcoming',
        ];
    }

    private function getSellerSales(Seller $seller): Collection
    {
        return TicketPurchase::query()
            ->where('seller_id', $seller->id)
            ->orWhere('seller_code', $seller->code)
            ->get()
            ->map(fn (TicketPurchase $ticket) => [
                'id' => $ticket->id,
                'reference' => $ticket->reference,
                'seller_id' => $ticket->seller_id,
                'seller_code' => $ticket->seller_code,
                'ticket_type_id' => $ticket->ticket_type_id,
                'ticket_type_label' => $ticket->ticket_type_label,
                'pricing_round_key' => $ticket->pricing_round_key,
                'pricing_round_label' => $ticket->pricing_round_label,
                'quantity' => $ticket->quantity,
                'buyer_phone' => $ticket->phone,
                'buyer_email' => $ticket->email,
                'buyer_name' => $ticket->buyer_name,
                'payment_method' => 'Cash',
                'total' => (float) $ticket->total,
                'synced' => (bool) ($ticket->synced ?? false),
                'email_sent' => (bool) ($ticket->email_sent ?? false),
                'ticket_code' => $ticket->ticket_code,
                'created_at' => $ticket->created_at->toIso8601String(),
            ]);
    }

    private function serializeSale(array $sale): array
    {
        return [
            'id' => $sale['id'],
            'reference' => $sale['reference'] ?? ('ZT-' . $sale['id']),
            'ticketType' => [
                'id' => $sale['ticket_type_id'],
                'name' => $sale['ticket_type_label'],
                'price' => $sale['total'] / max(1, $sale['quantity']),
            ],
            'quantity' => $sale['quantity'],
            'buyerPhone' => $sale['buyer_phone'],
            'buyerEmail' => $sale['buyer_email'],
            'buyerName' => $sale['buyer_name'],
            'paymentMethod' => $sale['payment_method'],
            'total' => $sale['total'],
            'timestamp' => $sale['created_at'],
            'synced' => $sale['synced'],
            'emailSent' => $sale['email_sent'],
            'ticketCode' => $sale['ticket_code'],
        ];
    }

    private function getCurrentRound(array $eventConfig): array
    {
        $salesConfig = $eventConfig['ticket_sales'] ?? [];
        $timezone = $salesConfig['timezone'] ?? 'Africa/Lusaka';
        $rounds = array_values($salesConfig['rounds'] ?? []);

        if (empty($rounds)) {
            return $rounds[0] ?? [];
        }

        $current = CarbonImmutable::now($timezone);
        $firstStart = CarbonImmutable::parse($rounds[0]['starts_at'], $timezone);

        if ($current < $firstStart) {
            return $rounds[0];
        }

        foreach ($rounds as $round) {
            $startsAt = CarbonImmutable::parse($round['starts_at'], $timezone);
            $endsAt = CarbonImmutable::parse($round['ends_at'], $timezone);

            if ($current->betweenIncluded($startsAt, $endsAt)) {
                return $round;
            }
        }

        return end($rounds);
    }

    private function generateReference(): string
    {
        return 'ZT-' . strtoupper(substr(uniqid(), -10));
    }
}
