<?php

namespace App\Http\Controllers\Api\V1;

use RuntimeException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use App\Services\LencoService;
use App\Services\CatalogService;
use App\Services\CheckoutService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\CreateEventTicketIntentRequest;

class EventTicketCheckoutController extends Controller
{
    public function createOnlineIntent(
        CreateEventTicketIntentRequest $request,
        CatalogService $catalogService,
        CheckoutService $checkoutService,
        LencoService $lencoService
    ): JsonResponse {
        try {
            [$event, $ticketType] = $catalogService->requireEventTicketType(
                $request->validated('eventSlug'),
                $request->validated('ticketTypeId'),
            );

            $catalogService->assertPaymentMethodAllowed(
                'event-ticket',
                $request->validated('currency'),
                $request->validated('paymentMethod'),
            );

            $created = $checkoutService->createPendingTicketPurchase(
                $event,
                $ticketType,
                $request->validated(),
            );
            $widgetIntent = $lencoService->buildWidgetIntent(
                $created['paymentIntent'],
                [$request->validated('paymentMethod')],
            );

            return response()->json([
                ...$widgetIntent,
                'ticketReference' => $created['ticketPurchase']->reference,
            ], 201);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 500);
        }
    }
}
