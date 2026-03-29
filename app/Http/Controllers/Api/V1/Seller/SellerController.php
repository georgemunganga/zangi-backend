<?php

namespace App\Http\Controllers\Api\V1\Seller;

use App\Http\Controllers\Controller;
use App\Services\Seller\SellerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SellerController extends Controller
{
    public function __construct(
        private readonly SellerService $sellerService,
    ) {
    }

    public function dashboard(Request $request): JsonResponse
    {
        /** @var \App\Models\Seller $seller */
        $seller = $request->user('seller');
        $date = $request->query('date');

        return response()->json(
            $this->sellerService->getDashboard($seller, $date),
        );
    }

    public function recentSales(Request $request): JsonResponse
    {
        /** @var \App\Models\Seller $seller */
        $seller = $request->user('seller');
        $limit = (int) $request->query('limit', 5);

        return response()->json(
            $this->sellerService->getRecentSales($seller, $limit),
        );
    }

    public function sales(Request $request): JsonResponse
    {
        /** @var \App\Models\Seller $seller */
        $seller = $request->user('seller');
        $filters = $request->query();

        return response()->json(
            $this->sellerService->getSales($seller, $filters),
        );
    }

    public function showSale(Request $request, int $id): JsonResponse
    {
        /** @var \App\Models\Seller $seller */
        $seller = $request->user('seller');
        $sale = $this->sellerService->getSale($seller, $id);

        if (! $sale) {
            return response()->json(['message' => 'Sale not found.'], 404);
        }

        return response()->json(['sale' => $sale]);
    }

    public function storeSale(Request $request): JsonResponse
    {
        throw ValidationException::withMessages([
            'sale' => 'Direct seller ticket creation is no longer allowed. Use the seller checkout payment endpoints.',
        ]);
    }

    public function markSynced(Request $request, int $id): JsonResponse
    {
        /** @var \App\Models\Seller $seller */
        $seller = $request->user('seller');

        $success = $this->sellerService->markSaleSynced($seller, $id);

        if (! $success) {
            return response()->json(['message' => 'Sale not found.'], 404);
        }

        return response()->json(['message' => 'Sale marked as synced.']);
    }

    public function bulkSync(Request $request): JsonResponse
    {
        throw ValidationException::withMessages([
            'sales' => 'Offline sale sync has been retired. Use the dedicated seller checkout payment endpoints.',
        ]);
    }

    public function ticketTypes(Request $request): JsonResponse
    {
        $eventId = $request->query('eventId');
        $result = $this->sellerService->getTicketTypes($eventId);

        return response()->json($result);
    }

    public function currentRound(Request $request): JsonResponse
    {
        $eventId = $request->query('eventId');
        $result = $this->sellerService->getCurrentRoundInfo($eventId);

        return response()->json($result);
    }

    public function event(Request $request, string $id): JsonResponse
    {
        $event = $this->sellerService->getEventDetails($id);

        if (! $event) {
            return response()->json(['message' => 'Event not found.'], 404);
        }

        return response()->json(['event' => $event]);
    }

    public function activeEvent(Request $request): JsonResponse
    {
        $event = $this->sellerService->getEventDetails('zangi-book-launch-mulungushi-lusaka');

        return response()->json(['event' => $event]);
    }

    public function resendEmail(Request $request, int $saleId): JsonResponse
    {
        /** @var \App\Models\Seller $seller */
        $seller = $request->user('seller');

        $sale = $this->sellerService->getSale($seller, $saleId);
        if (! $sale) {
            return response()->json(['message' => 'Sale not found.'], 404);
        }

        // TODO: Implement email resend logic
        return response()->json([
            'success' => true,
            'message' => 'Email sent successfully',
            'sentAt' => now()->toIso8601String(),
        ]);
    }
}
