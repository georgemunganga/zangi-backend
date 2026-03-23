<?php

namespace App\Http\Controllers\Api\V1\Admin;

use InvalidArgumentException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\Admin\AdminDataService;
use App\Services\Admin\AdminDocumentService;

class ReportController extends Controller
{
    public function summary(Request $request, AdminDataService $adminDataService): JsonResponse
    {
        return response()->json(
            $adminDataService->reportsSummary((string) $request->query('period', 'weekly')),
        );
    }

    public function export(
        Request $request,
        AdminDataService $adminDataService,
        AdminDocumentService $adminDocumentService,
    ) {
        try {
            return $adminDocumentService->exportReport(
                (string) $request->query('period', 'weekly'),
                (string) $request->query('format', 'csv'),
                $adminDataService,
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}
