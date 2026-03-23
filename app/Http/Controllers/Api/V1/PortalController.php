<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Order;
use App\Models\PortalUser;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\TicketPurchase;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class PortalController extends Controller
{
    public function overview(Request $request): JsonResponse
    {
        /** @var PortalUser $portalUser */
        $portalUser = $request->user();
        $this->attachOwnedRecords($portalUser);

        $orders = $this->ordersQuery($portalUser)->latest()->get();
        $tickets = $portalUser->supportsTickets()
            ? $this->ticketsQuery($portalUser)->latest()->get()
            : collect();

        return response()->json([
            'latestReference' => $this->latestReference($request, $portalUser),
            'metrics' => [
                ['label' => 'Orders', 'value' => $orders->count()],
                ['label' => 'Digital', 'value' => $orders->where('format', 'digital')->count()],
                ['label' => 'Tickets', 'value' => $tickets->count()],
            ],
            'recentActivity' => $this->recentActivity($orders, $tickets),
            'notes' => $portalUser->notes ?: [],
            'supportsTickets' => $portalUser->supportsTickets(),
        ]);
    }

    public function orders(Request $request): JsonResponse
    {
        /** @var PortalUser $portalUser */
        $portalUser = $request->user();
        $this->attachOwnedRecords($portalUser);

        $formatFilter = (string) $request->query('format', 'all');
        $query = $this->ordersQuery($portalUser);

        if (in_array($formatFilter, ['digital', 'hardcopy'], true)) {
            $query->where('format', $formatFilter);
        }

        $orders = $query->latest()->paginate(10);

        return response()->json([
            'data' => $orders->getCollection()->map(fn (Order $order) => $this->serializeOrder($order))->values(),
            'meta' => [
                'currentPage' => $orders->currentPage(),
                'lastPage' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function showOrder(Request $request, Order $order): JsonResponse
    {
        /** @var PortalUser $portalUser */
        $portalUser = $request->user();
        abort_unless($this->ownsOrder($portalUser, $order), 404);

        return response()->json($this->serializeOrder($order));
    }

    public function downloadOrder(Request $request, Order $order)
    {
        /** @var PortalUser $portalUser */
        $portalUser = $request->user();
        abort_unless($this->ownsOrder($portalUser, $order), 404);

        if (! $order->download_ready || ! $order->download_path) {
            return response()->json(['message' => 'This download is not ready yet.'], 409);
        }

        $disk = Storage::disk(config('filesystems.default'));

        if (! $disk->exists($order->download_path)) {
            return response()->json(['message' => 'The download file is not available yet.'], 404);
        }

        return $disk->download($order->download_path);
    }

    public function tickets(Request $request): JsonResponse
    {
        /** @var PortalUser $portalUser */
        $portalUser = $request->user();
        abort_unless($portalUser->supportsTickets(), 403);
        $this->attachOwnedRecords($portalUser);

        $tickets = $this->ticketsQuery($portalUser)
            ->latest()
            ->paginate(10);

        return response()->json([
            'data' => $tickets->getCollection()->map(fn (TicketPurchase $ticket) => $this->serializeTicket($ticket))->values(),
            'meta' => [
                'currentPage' => $tickets->currentPage(),
                'lastPage' => $tickets->lastPage(),
                'total' => $tickets->total(),
            ],
        ]);
    }

    public function showTicket(Request $request, TicketPurchase $ticketPurchase): JsonResponse
    {
        /** @var PortalUser $portalUser */
        $portalUser = $request->user();
        abort_unless($this->ownsTicket($portalUser, $ticketPurchase), 404);

        return response()->json($this->serializeTicket($ticketPurchase));
    }

    public function downloadTicketPass(Request $request, TicketPurchase $ticketPurchase)
    {
        /** @var PortalUser $portalUser */
        $portalUser = $request->user();
        abort_unless($this->ownsTicket($portalUser, $ticketPurchase), 404);

        if (! $ticketPurchase->pass_path) {
            return response()->json(['message' => 'The pass file is not ready yet.'], 409);
        }

        $disk = Storage::disk(config('filesystems.default'));

        if (! $disk->exists($ticketPurchase->pass_path)) {
            return response()->json(['message' => 'The pass file is not available yet.'], 404);
        }

        return $disk->download($ticketPurchase->pass_path);
    }

    private function latestReference(Request $request, PortalUser $portalUser): ?array
    {
        $orderQuery = trim((string) $request->query('order'));
        if ($orderQuery !== '') {
            $order = $this->ordersQuery($portalUser)
                ->where(fn ($query) => $query
                    ->where('reference', $orderQuery)
                    ->orWhere('id', ctype_digit($orderQuery) ? (int) $orderQuery : -1))
                ->latest()
                ->first();

            if ($order) {
                return [
                    'type' => 'order',
                    'reference' => $order->reference,
                    'status' => $order->status,
                    'title' => $order->product_title,
                ];
            }
        }

        $ticketQuery = trim((string) $request->query('ticket'));
        if ($ticketQuery !== '' && $portalUser->supportsTickets()) {
            $ticket = $this->ticketsQuery($portalUser)
                ->where(fn ($query) => $query
                    ->where('reference', $ticketQuery)
                    ->orWhere('id', ctype_digit($ticketQuery) ? (int) $ticketQuery : -1))
                ->latest()
                ->first();

            if ($ticket) {
                return [
                    'type' => 'ticket',
                    'reference' => $ticket->reference,
                    'status' => $ticket->status,
                    'title' => $ticket->event_title,
                ];
            }
        }

        return null;
    }

    private function recentActivity($orders, $tickets): array
    {
        return $orders->map(fn (Order $order) => [
            'type' => 'order',
            'reference' => $order->reference,
            'title' => $order->product_title,
            'status' => $order->status,
            'createdAt' => $order->created_at?->toIso8601String(),
        ])->merge(
            $tickets->map(fn (TicketPurchase $ticket) => [
                'type' => 'ticket',
                'reference' => $ticket->reference,
                'title' => $ticket->event_title,
                'status' => $ticket->status,
                'createdAt' => $ticket->created_at?->toIso8601String(),
            ]),
        )
            ->sortByDesc('createdAt')
            ->take(5)
            ->values()
            ->all();
    }

    private function attachOwnedRecords(PortalUser $portalUser): void
    {
        Order::query()
            ->whereNull('portal_user_id')
            ->where('email', $portalUser->email)
            ->update(['portal_user_id' => $portalUser->id]);

        TicketPurchase::query()
            ->whereNull('portal_user_id')
            ->where('email', $portalUser->email)
            ->update(['portal_user_id' => $portalUser->id]);
    }

    private function ordersQuery(PortalUser $portalUser)
    {
        return Order::query()
            ->where(function ($query) use ($portalUser) {
                $query
                    ->where('portal_user_id', $portalUser->id)
                    ->orWhere(function ($fallback) use ($portalUser) {
                        $fallback
                            ->whereNull('portal_user_id')
                            ->where('email', $portalUser->email);
                    });
            })
            ->where(function ($query) {
                $query
                    ->where('payment_status', 'Paid')
                    ->orWhere('payment_status', 'Pending on Delivery');
            });
    }

    private function ticketsQuery(PortalUser $portalUser)
    {
        return TicketPurchase::query()
            ->where(function ($query) use ($portalUser) {
                $query
                    ->where('portal_user_id', $portalUser->id)
                    ->orWhere(function ($fallback) use ($portalUser) {
                        $fallback
                            ->whereNull('portal_user_id')
                            ->where('email', $portalUser->email);
                    });
            })
            ->where('status', '!=', 'Pending');
    }

    private function ownsOrder(PortalUser $portalUser, Order $order): bool
    {
        if ($order->portal_user_id) {
            return (int) $order->portal_user_id === (int) $portalUser->id;
        }

        return $order->email === $portalUser->email;
    }

    private function ownsTicket(PortalUser $portalUser, TicketPurchase $ticketPurchase): bool
    {
        if ($ticketPurchase->portal_user_id) {
            return (int) $ticketPurchase->portal_user_id === (int) $portalUser->id;
        }

        return $ticketPurchase->email === $portalUser->email;
    }

    private function serializeOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'reference' => $order->reference,
            'productSlug' => $order->product_slug,
            'productTitle' => $order->product_title,
            'format' => $order->format,
            'quantity' => $order->quantity,
            'buyerType' => $order->buyer_type,
            'status' => $order->status,
            'createdAt' => optional($order->created_at)->toIso8601String(),
            'total' => (float) $order->total,
            'currency' => $order->currency,
            'timeline' => $order->timeline ?: [],
            'currentStep' => $order->current_step,
            'paymentStatus' => $order->payment_status,
            'paymentMethod' => $order->payment_method,
            'downloadReady' => (bool) $order->download_ready,
            'downloadUrl' => $order->download_ready ? "/api/v1/portal/orders/{$order->id}/download" : null,
        ];
    }

    private function serializeTicket(TicketPurchase $ticketPurchase): array
    {
        return [
            'id' => $ticketPurchase->id,
            'reference' => $ticketPurchase->reference,
            'eventSlug' => $ticketPurchase->event_slug,
            'eventTitle' => $ticketPurchase->event_title,
            'dateLabel' => $ticketPurchase->date_label,
            'timeLabel' => $ticketPurchase->time_label,
            'locationLabel' => $ticketPurchase->location_label,
            'ticketTypeId' => $ticketPurchase->ticket_type_id,
            'ticketTypeLabel' => $ticketPurchase->ticket_type_label,
            'pricingRoundKey' => $ticketPurchase->pricing_round_key,
            'pricingRoundLabel' => $ticketPurchase->pricing_round_label,
            'quantity' => $ticketPurchase->quantity,
            'unitPrice' => (float) $ticketPurchase->unit_price,
            'total' => (float) $ticketPurchase->total,
            'currency' => $ticketPurchase->currency,
            'status' => $ticketPurchase->status,
            'ticketCode' => $ticketPurchase->ticket_code,
            'ticketHolderName' => $ticketPurchase->ticket_holder_name,
            'buyerName' => $ticketPurchase->buyer_name,
            'qrUrl' => $ticketPurchase->qr_path,
            'passUrl' => $ticketPurchase->pass_path ? "/api/v1/portal/tickets/{$ticketPurchase->id}/pass" : null,
            'createdAt' => optional($ticketPurchase->created_at)->toIso8601String(),
        ];
    }
}
