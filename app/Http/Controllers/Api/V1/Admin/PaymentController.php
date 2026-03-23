<?php

namespace App\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\Admin\AdminDataService;
use App\Models\PaymentIntent;
use App\Services\Admin\AdminPaymentActionService;
use App\Http\Requests\Admin\PaymentNoteStoreRequest;

class PaymentController extends Controller
{
    public function index(Request $request, AdminDataService $adminDataService): JsonResponse
    {
        return response()->json($adminDataService->payments($request->query()));
    }

    public function show(int $paymentIntent, AdminDataService $adminDataService): JsonResponse
    {
        $record = $adminDataService->payment($paymentIntent);

        abort_unless($record, 404);

        return response()->json($record);
    }

    public function reconcile(
        PaymentIntent $paymentIntent,
        AdminPaymentActionService $adminPaymentActionService,
        AdminDataService $adminDataService,
    ): JsonResponse {
        $payment = $adminPaymentActionService->reconcile($paymentIntent);

        return response()->json([
            'message' => 'Payment reconciled as paid.',
            'payment' => $adminDataService->payment($payment->id),
        ]);
    }

    public function markFailed(
        PaymentIntent $paymentIntent,
        AdminPaymentActionService $adminPaymentActionService,
        AdminDataService $adminDataService,
    ): JsonResponse {
        $payment = $adminPaymentActionService->markFailed($paymentIntent);

        return response()->json([
            'message' => 'Payment marked as failed.',
            'payment' => $adminDataService->payment($payment->id),
        ]);
    }

    public function refund(
        PaymentIntent $paymentIntent,
        AdminPaymentActionService $adminPaymentActionService,
        AdminDataService $adminDataService,
    ): JsonResponse {
        $payment = $adminPaymentActionService->refund($paymentIntent);

        return response()->json([
            'message' => 'Payment refunded.',
            'payment' => $adminDataService->payment($payment->id),
        ]);
    }

    public function attachNote(
        PaymentIntent $paymentIntent,
        PaymentNoteStoreRequest $request,
        AdminPaymentActionService $adminPaymentActionService,
        AdminDataService $adminDataService,
    ): JsonResponse {
        $payment = $adminPaymentActionService->attachNote($paymentIntent, $request->validated('note'));

        return response()->json([
            'message' => 'Payment note attached.',
            'payment' => $adminDataService->payment($payment->id),
        ]);
    }
}
