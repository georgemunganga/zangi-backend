<?php

namespace App\Http\Controllers\Api\V1\Admin;

use InvalidArgumentException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\Admin\AdminDataService;
use App\Services\Admin\AdminDocumentService;
use Illuminate\Validation\ValidationException;
use App\Services\Admin\AdminTicketActionService;
use App\Http\Requests\Admin\TicketValidationRequest;

class TicketController extends Controller
{
    public function index(Request $request, AdminDataService $adminDataService): JsonResponse
    {
        return response()->json($adminDataService->tickets($request->query()));
    }

    public function show(int $ticketPurchase, AdminDataService $adminDataService): JsonResponse
    {
        $ticket = $adminDataService->ticket($ticketPurchase);

        abort_unless($ticket, 404);

        return response()->json($ticket);
    }

    public function download(
        int $ticketPurchase,
        AdminDataService $adminDataService,
        AdminDocumentService $adminDocumentService,
    ) {
        try {
            return $adminDocumentService->downloadTicketPass($ticketPurchase, $adminDataService);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 409);
        }
    }

    public function validateCode(
        TicketValidationRequest $request,
        AdminDataService $adminDataService,
        AdminTicketActionService $adminTicketActionService,
    ): JsonResponse {
        $result = $adminTicketActionService->validateCode(
            $request->validated('ticketCode'),
            $request->validated('eventSlug'),
        );

        return response()->json([
            'code' => $result['code'],
            'state' => $result['state'],
            'checkedAt' => $result['checkedAt'],
            'message' => $result['message'],
            'ticket' => $result['ticketId'] ? $adminDataService->ticket($result['ticketId']) : null,
        ]);
    }

    public function markUsed(
        int $ticketPurchase,
        AdminDataService $adminDataService,
        AdminTicketActionService $adminTicketActionService,
    ): JsonResponse {
        $ticket = \App\Models\TicketPurchase::query()->findOrFail($ticketPurchase);

        try {
            $updatedTicket = $adminTicketActionService->markUsed($ticket);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'ticket' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Ticket marked as used.',
            'ticket' => $adminDataService->ticket($updatedTicket->id),
        ]);
    }

    public function voidTicket(
        int $ticketPurchase,
        AdminDataService $adminDataService,
        AdminTicketActionService $adminTicketActionService,
    ): JsonResponse {
        $ticket = \App\Models\TicketPurchase::query()->findOrFail($ticketPurchase);

        try {
            $updatedTicket = $adminTicketActionService->voidTicket($ticket);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'ticket' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Ticket voided.',
            'ticket' => $adminDataService->ticket($updatedTicket->id),
        ]);
    }

    public function reissue(
        int $ticketPurchase,
        AdminDataService $adminDataService,
        AdminTicketActionService $adminTicketActionService,
    ): JsonResponse {
        $ticket = \App\Models\TicketPurchase::query()->findOrFail($ticketPurchase);

        try {
            $updatedTicket = $adminTicketActionService->reissue($ticket);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'ticket' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Ticket reissued.',
            'ticket' => $adminDataService->ticket($updatedTicket->id),
        ]);
    }

    public function resend(
        int $ticketPurchase,
        AdminDataService $adminDataService,
        AdminTicketActionService $adminTicketActionService,
        AdminDocumentService $adminDocumentService,
    ): JsonResponse {
        $ticket = \App\Models\TicketPurchase::query()->findOrFail($ticketPurchase);

        try {
            $updatedTicket = $adminTicketActionService->resend($ticket, $adminDocumentService);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'ticket' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Ticket resend email sent.',
            'ticket' => $adminDataService->ticket($updatedTicket->id),
        ]);
    }
}
