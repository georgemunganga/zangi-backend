<?php

namespace App\Http\Controllers\Api\V1\Seller;

use App\Http\Controllers\Controller;
use App\Services\Seller\SellerCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly SellerCheckoutService $sellerCheckoutService,
    ) {
    }

    public function createMobileMoneyIntent(Request $request): JsonResponse
    {
        /** @var \App\Models\Seller $seller */
        $seller = $request->user('seller');

        $validated = $request->validate([
            'eventId' => ['nullable', 'string', 'max:255'],
            'ticketTypeId' => ['required', 'string', 'max:64'],
            'quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'buyerPhone' => ['required', 'string', 'max:32'],
            'buyerEmail' => ['nullable', 'email:rfc,dns'],
            'buyerName' => ['nullable', 'string', 'max:255'],
            'buyerType' => ['nullable', 'in:individual,corporate'],
            'returnPath' => ['nullable', 'string', 'max:2048', 'regex:/^\/(?!\/)[^\s]*$/'],
        ]);

        try {
            $created = $this->sellerCheckoutService->createMobileMoneyIntent($seller, $validated);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 500);
        }

        return response()->json($created, 201);
    }

    public function verifyPayment(Request $request): JsonResponse
    {
        /** @var \App\Models\Seller $seller */
        $seller = $request->user('seller');

        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:120'],
        ]);

        try {
            $result = $this->sellerCheckoutService->verifyMobileMoneyPayment(
                $seller,
                $validated['reference'],
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 500);
        }

        return response()->json($result);
    }

    public function confirmManualDeposit(Request $request): JsonResponse
    {
        /** @var \App\Models\Seller $seller */
        $seller = $request->user('seller');

        $validated = $request->validate([
            'eventId' => ['nullable', 'string', 'max:255'],
            'ticketTypeId' => ['required', 'string', 'max:64'],
            'quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'buyerPhone' => ['required', 'string', 'max:32'],
            'buyerEmail' => ['nullable', 'email:rfc,dns'],
            'buyerName' => ['nullable', 'string', 'max:255'],
            'buyerType' => ['nullable', 'in:individual,corporate'],
            'depositReference' => ['nullable', 'string', 'max:120'],
            'depositNote' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $result = $this->sellerCheckoutService->confirmManualDeposit($seller, $validated);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 500);
        }

        return response()->json($result, 201);
    }
}
