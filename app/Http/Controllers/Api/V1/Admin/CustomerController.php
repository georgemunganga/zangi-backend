<?php

namespace App\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\Admin\AdminDataService;

class CustomerController extends Controller
{
    public function index(Request $request, AdminDataService $adminDataService): JsonResponse
    {
        return response()->json($adminDataService->customers($request->query()));
    }

    public function show(string $customer, AdminDataService $adminDataService): JsonResponse
    {
        $record = $adminDataService->customer($customer);

        abort_unless($record, 404);

        return response()->json($record);
    }
}
