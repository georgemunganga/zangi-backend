<?php

namespace App\Http\Controllers\Api\V1\Admin;

use InvalidArgumentException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\Admin\AdminDataService;
use App\Services\Admin\AdminDocumentService;
use Illuminate\Validation\ValidationException;
use App\Services\Admin\AdminOrderActionService;
use App\Services\Admin\AdminTicketActionService;
use App\Http\Requests\Admin\OrderStatusUpdateRequest;

class OrderController extends Controller
{
    public function index(Request $request, AdminDataService $adminDataService): JsonResponse
    {
        return response()->json($adminDataService->orders($request->query()));
    }

    public function show(string $record, AdminDataService $adminDataService): JsonResponse
    {
        $order = $adminDataService->order($record);

        abort_unless($order, 404);

        return response()->json($order);
    }

    public function invoice(
        string $record,
        AdminDataService $adminDataService,
        AdminDocumentService $adminDocumentService,
    ) {
        return $adminDocumentService->openOrderDocument($record, $adminDataService);
    }

    public function updateStatus(
        string $record,
        OrderStatusUpdateRequest $request,
        AdminOrderActionService $adminOrderActionService,
        AdminDataService $adminDataService,
    ): JsonResponse {
        try {
            $recordKey = $adminOrderActionService->updateStatus($record, $request->validated('status'));
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'status' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Order status updated.',
            'order' => $adminDataService->order($recordKey),
        ]);
    }

    public function confirmPayment(
        string $record,
        AdminOrderActionService $adminOrderActionService,
        AdminDataService $adminDataService,
    ): JsonResponse {
        $recordKey = $adminOrderActionService->confirmPayment($record);

        return response()->json([
            'message' => 'Order payment confirmed.',
            'order' => $adminDataService->order($recordKey),
        ]);
    }

    public function cancel(
        string $record,
        AdminOrderActionService $adminOrderActionService,
        AdminDataService $adminDataService,
    ): JsonResponse {
        $recordKey = $adminOrderActionService->cancel($record);

        return response()->json([
            'message' => 'Order cancelled.',
            'order' => $adminDataService->order($recordKey),
        ]);
    }

    public function refund(
        string $record,
        AdminOrderActionService $adminOrderActionService,
        AdminDataService $adminDataService,
    ): JsonResponse {
        $recordKey = $adminOrderActionService->refund($record);

        return response()->json([
            'message' => 'Order refunded.',
            'order' => $adminDataService->order($recordKey),
        ]);
    }

    public function resend(
        string $record,
        AdminOrderActionService $adminOrderActionService,
        AdminDataService $adminDataService,
        AdminDocumentService $adminDocumentService,
        AdminTicketActionService $adminTicketActionService,
    ): JsonResponse {
        try {
            $result = $adminOrderActionService->resendDelivery(
                $record,
                $adminDocumentService,
                $adminTicketActionService,
            );
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'order' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'message' => $result['message'],
            'order' => $adminDataService->order($result['recordKey']),
        ]);
    }
}
