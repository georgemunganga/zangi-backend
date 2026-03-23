<?php

namespace Tests\Feature\Api\V1;

use App\Models\Order;
use App\Models\PortalUser;
use Illuminate\Support\Facades\Config;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutAccountIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_online_checkout_intent_does_not_overwrite_existing_portal_profile(): void
    {
        $portalUser = PortalUser::create([
            'role' => 'individual',
            'portal_mode' => 'individual',
            'group_type' => null,
            'has_individual_access' => true,
            'has_group_access' => false,
            'name' => 'Existing Buyer',
            'email' => 'buyer@gmail.com',
            'phone' => '+260111111111',
            'organization_name' => 'Original Org',
            'headline' => 'Original headline',
            'notes' => ['keep-this'],
        ]);

        Config::set('services.lenco.public_key', 'test-public-key');
        Config::set('services.lenco.redirect_base_url', 'https://app.zangisworld.test');

        $this->postJson('/api/v1/payments/lenco/intent', [
            'purchaseType' => 'book-order',
            'buyerType' => 'wholesale',
            'email' => 'buyer@gmail.com',
            'phone' => '+260999999999',
            'channel' => 'card',
            'currency' => 'USD',
            'returnPath' => '/checkout/zangi-flag-of-kindness?format=hardcopy',
            'customerName' => 'Spoofed Buyer',
            'metadata' => [
                'productSlug' => 'zangi-flag-of-kindness',
                'formatType' => 'hardcopy',
                'quantity' => 1,
                'organizationName' => 'Overwritten Org',
            ],
        ])
            ->assertCreated()
            ->assertJsonStructure(['reference', 'amount', 'currency', 'publicKey', 'channels', 'redirectUrl']);

        $portalUser->refresh();

        $this->assertSame('individual', $portalUser->role);
        $this->assertSame('individual', $portalUser->portal_mode);
        $this->assertNull($portalUser->group_type);
        $this->assertTrue($portalUser->has_individual_access);
        $this->assertFalse($portalUser->has_group_access);
        $this->assertSame('Existing Buyer', $portalUser->name);
        $this->assertSame('+260111111111', $portalUser->phone);
        $this->assertSame('Original Org', $portalUser->organization_name);
        $this->assertSame('Original headline', $portalUser->headline);
        $this->assertSame(['keep-this'], $portalUser->notes);

        $order = Order::query()->latest('id')->firstOrFail();

        $this->assertSame($portalUser->id, $order->portal_user_id);
        $this->assertSame('wholesale', $order->buyer_type);
        $this->assertSame('buyer@gmail.com', $order->email);
    }

    public function test_cod_checkout_does_not_overwrite_existing_portal_profile(): void
    {
        $portalUser = PortalUser::create([
            'role' => 'corporate',
            'portal_mode' => 'group',
            'group_type' => 'corporate',
            'has_individual_access' => false,
            'has_group_access' => true,
            'name' => 'School Account',
            'email' => 'school@gmail.com',
            'phone' => '+260222222222',
            'organization_name' => 'Existing School',
            'headline' => 'School headline',
            'notes' => ['existing-note'],
        ]);

        $this->postJson('/api/v1/checkout/book-orders/cod', [
            'productSlug' => 'zangi-flag-of-kindness',
            'formatType' => 'hardcopy',
            'quantity' => 2,
            'buyerType' => 'corporate',
            'email' => 'school@gmail.com',
            'phone' => '+260888888888',
            'organizationName' => 'Tampered School Name',
            'currency' => 'ZMW',
        ])
            ->assertCreated()
            ->assertJsonPath('paymentStatus', 'Pending on Delivery');

        $portalUser->refresh();

        $this->assertSame('corporate', $portalUser->role);
        $this->assertSame('group', $portalUser->portal_mode);
        $this->assertSame('corporate', $portalUser->group_type);
        $this->assertFalse($portalUser->has_individual_access);
        $this->assertTrue($portalUser->has_group_access);
        $this->assertSame('School Account', $portalUser->name);
        $this->assertSame('+260222222222', $portalUser->phone);
        $this->assertSame('Existing School', $portalUser->organization_name);
        $this->assertSame('School headline', $portalUser->headline);
        $this->assertSame(['existing-note'], $portalUser->notes);

        $order = Order::query()->latest('id')->firstOrFail();

        $this->assertSame($portalUser->id, $order->portal_user_id);
        $this->assertSame('school@gmail.com', $order->email);
        $this->assertSame('corporate', $order->buyer_type);
        $this->assertSame('Tampered School Name', $order->organization_name);
    }
}
