<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use App\Http\Requests\Newsletter\StoreNewsletterSubscriberRequest;

class NewsletterSubscriberController extends Controller
{
    public function store(StoreNewsletterSubscriberRequest $request): JsonResponse
    {
        $subscriber = NewsletterSubscriber::firstOrCreate(
            ['email' => $request->validated('email')],
            ['subscribed_at' => now()],
        );

        return response()->json([
            'message' => $subscriber->wasRecentlyCreated
                ? 'You are now subscribed.'
                : 'This email is already subscribed.',
            'subscriberId' => $subscriber->id,
        ], $subscriber->wasRecentlyCreated ? 201 : 200);
    }
}
