<?php

namespace App\Services\Admin;

use App\Mail\ContactMessageReplyMail;
use App\Models\AdminUser;
use App\Models\ContactMessage;
use App\Models\ContactMessageReply;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class AdminContactMessageActionService
{
    public function updateStatus(ContactMessage $contactMessage, string $status): ContactMessage
    {
        $contactMessage->forceFill([
            'status' => $status,
        ])->save();

        return $contactMessage->fresh();
    }

    public function reply(ContactMessage $contactMessage, string $body, AdminUser $adminUser): ContactMessageReply
    {
        $reply = DB::transaction(function () use ($contactMessage, $body, $adminUser): ContactMessageReply {
            $contactMessage->forceFill([
                'status' => 'replied',
            ])->save();

            return ContactMessageReply::query()->create([
                'contact_message_id' => $contactMessage->id,
                'author_type' => 'admin',
                'author_name' => $adminUser->name,
                'body' => trim($body),
                'sent_at' => now(),
            ]);
        });

        if (filled($contactMessage->email)) {
            Mail::to($contactMessage->email)->send(
                new ContactMessageReplyMail($contactMessage->fresh(), $reply->fresh()),
            );
        }

        return $reply;
    }
}
