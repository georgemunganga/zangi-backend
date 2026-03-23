<?php

namespace App\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\Admin\AdminDataService;
use App\Models\ContactMessage;
use App\Services\Admin\AdminContactMessageActionService;
use App\Http\Requests\Admin\ContactMessageReplyStoreRequest;
use App\Http\Requests\Admin\ContactMessageStatusUpdateRequest;

class ContactMessageController extends Controller
{
    public function index(Request $request, AdminDataService $adminDataService): JsonResponse
    {
        return response()->json($adminDataService->contactMessages($request->query()));
    }

    public function show(int $contactMessage, AdminDataService $adminDataService): JsonResponse
    {
        $record = $adminDataService->contactMessage($contactMessage);

        abort_unless($record, 404);

        return response()->json($record);
    }

    public function reply(
        ContactMessage $contactMessage,
        ContactMessageReplyStoreRequest $request,
        AdminContactMessageActionService $adminContactMessageActionService,
        AdminDataService $adminDataService,
    ): JsonResponse {
        $adminContactMessageActionService->reply($contactMessage, $request->validated('body'), $request->user('admin'));

        return response()->json([
            'message' => 'Reply stored successfully.',
            'contactMessage' => $adminDataService->contactMessage($contactMessage->id),
        ]);
    }

    public function updateStatus(
        ContactMessage $contactMessage,
        ContactMessageStatusUpdateRequest $request,
        AdminContactMessageActionService $adminContactMessageActionService,
        AdminDataService $adminDataService,
    ): JsonResponse {
        $updatedMessage = $adminContactMessageActionService->updateStatus(
            $contactMessage,
            $request->validated('status'),
        );

        return response()->json([
            'message' => 'Contact message status updated.',
            'contactMessage' => $adminDataService->contactMessage($updatedMessage->id),
        ]);
    }
}
