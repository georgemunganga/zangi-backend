<?php

namespace Tests\Feature\Api\V1;

use App\Models\AdminUser;
use App\Models\PortalUser;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Models\PortalOtpChallenge;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthAndRateLimitSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_otp_is_rate_limited_per_email(): void
    {
        PortalUser::create([
            'role' => 'individual',
            'portal_mode' => 'individual',
            'group_type' => null,
            'has_individual_access' => true,
            'has_group_access' => false,
            'name' => 'Portal Buyer',
            'email' => 'buyer@gmail.com',
            'phone' => '+260111111111',
            'organization_name' => null,
            'headline' => 'Buyer',
            'notes' => [],
        ]);

        for ($attempt = 0; $attempt < 8; $attempt += 1) {
            $this->postJson('/api/v1/auth/request-otp', [
                'email' => 'buyer@gmail.com',
            ])->assertStatus(202);
        }

        $this->postJson('/api/v1/auth/request-otp', [
            'email' => 'buyer@gmail.com',
        ])
            ->assertStatus(429)
            ->assertJsonPath('message', 'Too many requests. Please wait and try again.');
    }

    public function test_verify_otp_locks_challenge_after_too_many_invalid_attempts(): void
    {
        $portalUser = PortalUser::create([
            'role' => 'individual',
            'portal_mode' => 'individual',
            'group_type' => null,
            'has_individual_access' => true,
            'has_group_access' => false,
            'name' => 'Portal Buyer',
            'email' => 'buyer@gmail.com',
            'phone' => '+260111111111',
            'organization_name' => null,
            'headline' => 'Buyer',
            'notes' => [],
        ]);

        $challenge = PortalOtpChallenge::create([
            'portal_user_id' => $portalUser->id,
            'email' => $portalUser->email,
            'role' => $portalUser->role,
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'consumed_at' => null,
        ]);

        for ($attempt = 0; $attempt < 4; $attempt += 1) {
            $this->postJson('/api/v1/auth/verify-otp', [
                'email' => 'buyer@gmail.com',
                'code' => '000000',
            ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['code']);
        }

        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => 'buyer@gmail.com',
            'code' => '000000',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);

        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => 'buyer@gmail.com',
            'code' => '123456',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);

        $challenge->refresh();

        $this->assertSame(5, $challenge->attempts);
        $this->assertNotNull($challenge->consumed_at);
    }

    public function test_admin_login_is_rate_limited_after_repeated_failures(): void
    {
        AdminUser::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        for ($attempt = 0; $attempt < 5; $attempt += 1) {
            $this->postJson('/api/v1/admin/auth/login', [
                'email' => 'admin@example.com',
                'password' => 'wrong-password',
            ])->assertStatus(422);
        }

        $this->postJson('/api/v1/admin/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ])
            ->assertStatus(429)
            ->assertJsonPath('message', 'Too many requests. Please wait and try again.');
    }

    public function test_cod_checkout_is_rate_limited_to_reduce_spam(): void
    {
        for ($attempt = 0; $attempt < 6; $attempt += 1) {
            $this->postJson('/api/v1/checkout/book-orders/cod', [
                'productSlug' => 'zangi-flag-of-kindness',
                'formatType' => 'hardcopy',
                'quantity' => 1,
                'buyerType' => 'individual',
                'email' => 'buyer@gmail.com',
                'phone' => '+260111111111',
                'currency' => 'ZMW',
            ])->assertStatus(201);
        }

        $this->postJson('/api/v1/checkout/book-orders/cod', [
            'productSlug' => 'zangi-flag-of-kindness',
            'formatType' => 'hardcopy',
            'quantity' => 1,
            'buyerType' => 'individual',
            'email' => 'buyer@gmail.com',
            'phone' => '+260111111111',
            'currency' => 'ZMW',
        ])
            ->assertStatus(429)
            ->assertJsonPath('message', 'Too many requests. Please wait and try again.');
    }
}
