<?php

namespace App\Http\Controllers\Api\V1;

use Throwable;
use Illuminate\Http\JsonResponse;
use App\Mail\ContactMessageAdminMail;
use App\Models\ContactMessage;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactMessageReceivedMail;
use App\Http\Controllers\Controller;
use App\Http\Requests\Contact\StoreContactMessageRequest;

class ContactMessageController extends Controller
{
    public function store(StoreContactMessageRequest $request): JsonResponse
    {
        $message = ContactMessage::create($request->validated());
        $this->sendContactEmails($message);

        return response()->json([
            'message' => 'Your message has been received.',
            'contactMessageId' => $message->id,
        ], 201);
    }

    private function sendContactEmails(ContactMessage $message): void
    {
        try {
            Mail::to($message->email)->send(new ContactMessageReceivedMail($message));
        } catch (Throwable $error) {
            report($error);
        }

        $adminEmail = config('mail.from.address');

        if (! $adminEmail) {
            return;
        }

        try {
            Mail::to($adminEmail)->send(new ContactMessageAdminMail($message));
        } catch (Throwable $error) {
            report($error);
        }
    }
}
