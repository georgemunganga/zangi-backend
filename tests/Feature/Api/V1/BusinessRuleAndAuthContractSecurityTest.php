<?php

namespace Tests\Feature\Api\V1;

use App\Models\PortalUser;
use App\Models\RefreshToken;
use App\Services\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessRuleAndAuthContractSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_general_payment_intent_rejects_zambian_digital_book_orders(): void
    {
        $this->postJson('/api/v1/payments/lenco/intent', [
            'purchaseType' => 'book-order',
            'buyerType' => 'individual',
            'email' => 'buyer@gmail.com',
            'phone' => '+260111111111',
            'channel' => 'mobile-money',
            'currency' => 'ZMW',
            'returnPath' => '/checkout/zangi-flag-of-kindness?format=digital',
            'customerName' => 'Buyer',
            'metadata' => [
                'productSlug' => 'zangi-flag-of-kindness',
                'formatType' => 'digital',
                'quantity' => 1,
            ],
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Digital book orders are not available for Zambia checkout.');
    }

    public function test_book_checkout_intent_rejects_zambian_digital_book_orders(): void
    {
        $this->postJson('/api/v1/checkout/book-orders/online-intent', [
            'productSlug' => 'zangi-flag-of-kindness',
            'formatType' => 'digital',
            'quantity' => 1,
            'buyerType' => 'individual',
            'email' => 'buyer@gmail.com',
            'phone' => '+260111111111',
            'currency' => 'ZMW',
            'paymentMethod' => 'mobile-money',
            'returnPath' => '/checkout/zangi-flag-of-kindness?format=digital',
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Digital book orders are not available for Zambia checkout.');
    }

    public function test_request_otp_response_is_generic_for_existing_and_missing_accounts(): void
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

        $existingResponse = $this->postJson('/api/v1/auth/request-otp', [
            'email' => 'buyer@gmail.com',
        ]);
        $missingResponse = $this->postJson('/api/v1/auth/request-otp', [
            'email' => 'missing@gmail.com',
        ]);

        $existingResponse
            ->assertStatus(202)
            ->assertJsonStructure(['message', 'email', 'expiresAt', 'devOtpCode'])
            ->assertJsonMissingPath('role')
            ->assertJsonMissingPath('portalMode')
            ->assertJsonMissingPath('groupType')
            ->assertJsonMissingPath('hasIndividualAccess')
            ->assertJsonMissingPath('hasGroupAccess');

        $missingResponse
            ->assertStatus(202)
            ->assertJsonStructure(['message', 'email', 'expiresAt', 'devOtpCode'])
            ->assertJsonMissingPath('role')
            ->assertJsonMissingPath('portalMode')
            ->assertJsonMissingPath('groupType')
            ->assertJsonMissingPath('hasIndividualAccess')
            ->assertJsonMissingPath('hasGroupAccess');
    }

    public function test_refresh_tokens_use_lookup_key_and_refresh_successfully(): void
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

        $tokens = app(TokenService::class)->issueTokenPair($portalUser);

        $this->assertStringContainsString('.', $tokens['refreshToken']);

        $storedRefreshToken = RefreshToken::query()->firstOrFail();

        $this->assertNotNull($storedRefreshToken->token_lookup);

        $this->postJson('/api/v1/auth/refresh', [
            'refreshToken' => $tokens['refreshToken'],
        ])
            ->assertOk()
            ->assertJsonStructure(['accessToken', 'refreshToken', 'tokenType', 'expiresIn']);

        $this->assertNotNull($storedRefreshToken->fresh()->revoked_at);
        $this->assertDatabaseCount('refresh_tokens', 2);
    }
}
