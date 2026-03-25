<?php

namespace App\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\Admin\AdminDataService;

class EventController extends Controller
{
    public function index(AdminDataService $adminDataService): JsonResponse
    {
        return response()->json([
            'data' => $adminDataService->eventsWithTicketTypes(),
        ]);
    }
}
