<?php

namespace App\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\Admin\AdminDataService;

class OverviewController extends Controller
{
    public function __invoke(AdminDataService $adminDataService): JsonResponse
    {
        return response()->json($adminDataService->overview());
    }
}
