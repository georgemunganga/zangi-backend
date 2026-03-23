<?php

namespace Tests\Feature\Api\V1\Admin;

use Tests\TestCase;
use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_fetch_profile_change_password_and_logout(): void
    {
        $adminUser = AdminUser::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $loginResponse = $this->postJson('/api/v1/admin/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonPath('user.email', 'admin@example.com');

        $token = $loginResponse->json('accessToken');

        $this->withToken($token)
            ->getJson('/api/v1/admin/auth/me')
            ->assertOk()
            ->assertJsonPath('user.id', $adminUser->id);

        $this->withToken($token)
            ->postJson('/api/v1/admin/auth/change-password', [
                'currentPassword' => 'password123',
                'newPassword' => 'new-password-123',
                'newPasswordConfirmation' => 'new-password-123',
            ])
            ->assertOk()
            ->assertJsonPath('user.email', 'admin@example.com');

        $this->withToken($token)
            ->postJson('/api/v1/admin/auth/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->postJson('/api/v1/admin/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'new-password-123',
        ])->assertOk();
    }

    public function test_admin_login_rejects_invalid_credentials(): void
    {
        AdminUser::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $this->postJson('/api/v1/admin/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(422);
    }
}
