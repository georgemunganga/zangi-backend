<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use App\Models\ContactMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Contact\StoreContactMessageRequest;

class ContactMessageController extends Controller
{
    public function store(StoreContactMessageRequest $request): JsonResponse
    {
        $message = ContactMessage::create($request->validated());

        return response()->json([
            'message' => 'Your message has been received.',
            'contactMessageId' => $message->id,
        ], 201);
    }
}
