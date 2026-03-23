<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Order;
use RuntimeException;
use Illuminate\Http\Request;
use App\Models\PaymentIntent;
use Illuminate\Http\JsonResponse;
use App\Models\TicketPurchase;
use InvalidArgumentException;
use App\Services\LencoService;
use App\Services\CatalogService;
use App\Services\CheckoutService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\VerifyLencoPaymentRequest;

class PaymentController extends Controller
{
    public function intent(
        Request $request,
        CatalogService $catalogService,
        CheckoutService $checkoutService,
        LencoService $lencoService
    ): JsonResponse {
        $validated = $request->validate([
            'purchaseType' => ['required', 'in:book-order,event-ticket'],
            'buyerType' => ['required', 'string'],
            'email' => ['required', 'email:rfc,dns'],
            'phone' => ['required', 'string', 'max:32'],
            'channel' => ['required', 'in:mobile-money,card'],
            'currency' => ['required', 'in:ZMW,USD'],
            'returnPath' => ['required', 'string', 'max:2048', 'regex:/^\/(?!\/)[^\s]*$/'],
            'customerName' => ['nullable', 'string', 'max:255'],
            'metadata' => ['required', 'array'],
        ]);

        try {
            if ($validated['purchaseType'] === 'book-order') {
                if (! in_array($validated['buyerType'], $catalogService->buyerTypes(), true)) {
                    throw new InvalidArgumentException('That buyer type is not allowed for book checkout.');
                }

                $formatType = data_get($validated, 'metadata.format') ?: data_get($validated, 'metadata.formatType');
                [$book, $format] = $catalogService->requireBookFormat(
                    (string) data_get($validated, 'metadata.productSlug'),
                    (string) $formatType,
                );
                $catalogService->assertBookFormatAvailableForCurrency(
                    $validated['currency'],
                    $format['type'],
                );

                $catalogService->assertPaymentMethodAllowed(
                    'book-order',
                    $validated['currency'],
                    $validated['channel'],
                    $format['type'],
                );

                $created = $checkoutService->createPendingBookOrder($book, $format, [
                    'buyerType' => $validated['buyerType'],
                    'email' => strtolower(trim($validated['email'])),
                    'phone' => trim($validated['phone']),
                    'organizationName' => data_get($validated, 'metadata.organizationName'),
                    'currency' => $validated['currency'],
                    'paymentMethod' => $validated['channel'],
                    'quantity' => (int) data_get($validated, 'metadata.quantity', 1),
                    'returnPath' => $validated['returnPath'],
                ]);

                $widgetIntent = $lencoService->buildWidgetIntent(
                    $created['paymentIntent'],
                    [$validated['channel']],
                );

                return response()->json([
                    ...$widgetIntent,
                    'orderReference' => $created['order']->reference,
                ], 201);
            }

            if (! in_array($validated['buyerType'], ['individual', 'corporate'], true)) {
                throw new InvalidArgumentException('That buyer type is not allowed for event tickets.');
            }

            [$event, $ticketType] = $catalogService->requireEventTicketType(
                (string) data_get($validated, 'metadata.eventSlug'),
                (string) data_get($validated, 'metadata.ticketTypeId'),
            );

            $catalogService->assertPaymentMethodAllowed(
                'event-ticket',
                $validated['currency'],
                $validated['channel'],
            );

            $created = $checkoutService->createPendingTicketPurchase($event, $ticketType, [
                'buyerType' => $validated['buyerType'],
                'email' => strtolower(trim($validated['email'])),
                'phone' => trim($validated['phone']),
                'organizationName' => data_get($validated, 'metadata.organizationName'),
                'currency' => $validated['currency'],
                'paymentMethod' => $validated['channel'],
                'quantity' => (int) data_get($validated, 'metadata.quantity', 1),
                'returnPath' => $validated['returnPath'],
                'customerName' => $validated['customerName'] ?: $validated['email'],
                'ticketHolderName' => data_get($validated, 'metadata.ticketHolderName') ?: $validated['email'],
            ]);

            $widgetIntent = $lencoService->buildWidgetIntent(
                $created['paymentIntent'],
                [$validated['channel']],
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

    public function verify(
        VerifyLencoPaymentRequest $request,
        LencoService $lencoService,
        CheckoutService $checkoutService
    ): JsonResponse {
        $paymentIntent = PaymentIntent::query()
            ->where('reference', $request->validated('reference'))
            ->first();

        if (! $paymentIntent) {
            return response()->json(['message' => 'Payment reference not found.'], 404);
        }

        try {
            $verification = $lencoService->verifyCollection($paymentIntent);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 500);
        }

        [$purchaseType, $purchase] = $this->applyVerification(
            $paymentIntent,
            $verification,
            $checkoutService,
        );

        return response()->json($this->verificationPayload($paymentIntent, $verification, $purchaseType, $purchase));
    }

    public function webhook(
        Request $request,
        LencoService $lencoService,
        CheckoutService $checkoutService
    ): JsonResponse {
        if (! $lencoService->verifyWebhookSignature($request)) {
            return response()->json(['message' => 'Invalid webhook signature.'], 403);
        }

        $reference = (string) data_get($request->all(), 'data.reference', data_get($request->all(), 'reference', ''));

        if ($reference === '') {
            return response()->json(['message' => 'Missing payment reference.'], 422);
        }

        $paymentIntent = PaymentIntent::query()->where('reference', $reference)->first();

        if (! $paymentIntent) {
            return response()->json(['received' => true], 202);
        }

        $status = strtolower((string) data_get($request->all(), 'data.status', data_get($request->all(), 'status', 'pending')));
        $paid = in_array($status, ['successful', 'success', 'paid', 'completed'], true);
        $pending = in_array($status, ['pending', 'processing', 'confirmation_pending'], true);

        if ($paid) {
            try {
                $verification = $lencoService->verifyCollection($paymentIntent);
            } catch (RuntimeException $exception) {
                return response()->json(['message' => $exception->getMessage()], 500);
            }

            $this->applyVerification($paymentIntent, $verification, $checkoutService);
        } else {
            $paymentIntent->forceFill([
                'status' => $pending ? 'Pending' : 'Failed',
                'lenco_response' => $request->all(),
            ])->save();
        }

        return response()->json(['received' => true]);
    }

    private function applyVerification(
        PaymentIntent $paymentIntent,
        array $verification,
        CheckoutService $checkoutService
    ): array {
        $paymentIntent->forceFill([
            'status' => $verification['paid'] ? 'Paid' : ($verification['pending'] ? 'Pending' : 'Failed'),
            'verified_at' => $verification['paid'] ? now() : $paymentIntent->verified_at,
            'lenco_response' => $verification['lencoResponse'],
        ])->save();

        $purchase = null;
        $purchaseType = $paymentIntent->purchase_type;

        if ($verification['paid']) {
            $finalized = $checkoutService->finalizePaymentIntent($paymentIntent);
            $purchaseType = $finalized['purchaseType'];
            $purchase = $finalized['purchase'];
        }

        return [$purchaseType, $purchase];
    }

    private function verificationPayload(
        PaymentIntent $paymentIntent,
        array $verification,
        string $purchaseType,
        Order|TicketPurchase|null $purchase
    ): array {
        $purchaseReference = $purchase?->reference;
        $purchaseId = $purchase?->id;
        $queryKey = $purchaseType === 'event-ticket' ? 'ticket' : 'order';

        return [
            'reference' => $paymentIntent->reference,
            'status' => $verification['status'],
            'paid' => $verification['paid'],
            'pending' => $verification['pending'],
            'purchaseType' => $purchaseType,
            'purchaseId' => $purchaseId,
            'purchaseReference' => $purchaseReference,
            'portalRedirect' => $purchase
                ? "/portal/login?role={$paymentIntent->buyer_type}&{$queryKey}={$purchaseId}&email=".urlencode($paymentIntent->email)
                : null,
            'methodLabel' => $verification['methodLabel'],
            'provider' => $verification['provider'],
            'accountName' => $verification['accountName'],
            'maskedAccount' => $verification['maskedAccount'],
            'currency' => $verification['currency'],
            'verifiedAt' => $verification['verifiedAt'],
            'lencoResponse' => $verification['lencoResponse'],
        ];
    }
}
