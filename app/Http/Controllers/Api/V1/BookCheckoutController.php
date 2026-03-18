<?php

namespace App\Http\Controllers\Api\V1;

use RuntimeException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use App\Services\LencoService;
use App\Services\CatalogService;
use App\Services\CheckoutService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\CreateBookCodOrderRequest;
use App\Http\Requests\Checkout\CreateBookOrderIntentRequest;

class BookCheckoutController extends Controller
{
    public function createOnlineIntent(
        CreateBookOrderIntentRequest $request,
        CatalogService $catalogService,
        CheckoutService $checkoutService,
        LencoService $lencoService
    ): JsonResponse {
        try {
            [$book, $format] = $catalogService->requireBookFormat(
                $request->validated('productSlug'),
                $request->validated('formatType'),
            );

            $catalogService->assertPaymentMethodAllowed(
                'book-order',
                $request->validated('currency'),
                $request->validated('paymentMethod'),
                $request->validated('formatType'),
            );

            $created = $checkoutService->createPendingBookOrder($book, $format, $request->validated());
            $widgetIntent = $lencoService->buildWidgetIntent(
                $created['paymentIntent'],
                [$request->validated('paymentMethod')],
            );

            return response()->json([
                ...$widgetIntent,
                'orderReference' => $created['order']->reference,
            ], 201);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 500);
        }
    }

    public function createCashOnDeliveryOrder(
        CreateBookCodOrderRequest $request,
        CatalogService $catalogService,
        CheckoutService $checkoutService
    ): JsonResponse {
        try {
            [$book, $format] = $catalogService->requireBookFormat(
                $request->validated('productSlug'),
                'hardcopy',
            );

            $catalogService->assertPaymentMethodAllowed(
                'book-order',
                $request->validated('currency'),
                'cash-on-delivery',
                'hardcopy',
            );

            $order = $checkoutService->createCashOnDeliveryBookOrder(
                $book,
                $format,
                $request->validated(),
            );

            return response()->json([
                'orderId' => $order->id,
                'orderReference' => $order->reference,
                'paymentStatus' => $order->payment_status,
                'portalRedirect' => "/portal/login?role={$order->buyer_type}&order={$order->id}&email=".urlencode($order->email),
            ], 201);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }
}
