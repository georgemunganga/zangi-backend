<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Mail\ContactMessageReplyMail;
use App\Models\AdminUser;
use App\Models\ContactMessage;
use Tests\TestCase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminContactMessageActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_contact_status_and_store_reply(): void
    {
        Mail::fake();

        $token = AdminUser::factory()->create([
            'name' => 'Operations Lead',
        ])->createToken('admin-access')->plainTextToken;

        $contactMessage = ContactMessage::query()->create([
            'name' => 'Inbox Customer',
            'email' => 'inbox@example.com',
            'message' => 'I need help with my ticket delivery.',
            'status' => 'new',
        ]);

        $this->withToken($token)->postJson("/api/v1/admin/contact-messages/{$contactMessage->id}/status", [
            'status' => 'in_progress',
        ])
            ->assertOk()
            ->assertJsonPath('contactMessage.status', 'in_progress');

        $this->withToken($token)->postJson("/api/v1/admin/contact-messages/{$contactMessage->id}/reply", [
            'body' => 'We have queued a resend and will confirm once it is delivered.',
        ])
            ->assertOk()
            ->assertJsonPath('contactMessage.status', 'replied')
            ->assertJsonPath('contactMessage.thread.1.author', 'admin')
            ->assertJsonPath('contactMessage.thread.1.name', 'Operations Lead');

        $this->assertDatabaseHas('contact_message_replies', [
            'contact_message_id' => $contactMessage->id,
            'author_name' => 'Operations Lead',
        ]);

        Mail::assertSent(ContactMessageReplyMail::class, function (ContactMessageReplyMail $mail) use ($contactMessage): bool {
            return $mail->hasTo('inbox@example.com')
                && $mail->contactMessage->is($contactMessage)
                && trim($mail->reply->body) === 'We have queued a resend and will confirm once it is delivered.';
        });
    }
}
