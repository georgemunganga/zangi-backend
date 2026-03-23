<?php

namespace App\Http\Controllers\Api\V1\Admin;

use InvalidArgumentException;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use App\Services\Admin\AdminDataService;
use App\Http\Requests\Admin\ManualSalesStoreRequest;
use App\Services\Admin\AdminManualSalesService;

class ManualSalesController extends Controller
{
    public function store(
        ManualSalesStoreRequest $request,
        AdminManualSalesService $adminManualSalesService,
        AdminDataService $adminDataService,
    ): JsonResponse {
        try {
            $result = $adminManualSalesService->create($request->validated());
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'saleType' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Manual sale created successfully.',
            'saleType' => $result['saleType'],
            'order' => $adminDataService->order($result['orderRecordKey']),
            'tickets' => collect($result['ticketIds'])
                ->map(fn (int $ticketId) => $adminDataService->ticket($ticketId))
                ->filter()
                ->values()
                ->all(),
            'payment' => $adminDataService->payment($result['paymentIntentId']),
        ], 201);
    }
}
