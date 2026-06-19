<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponQrRedeemTest extends TestCase
{
    use RefreshDatabase;

    private Site $site;

    private Coupon $coupon;

    protected function setUp(): void
    {
        parent::setUp();

        $this->site = Site::factory()->create([
            'name' => 'Sitio 1',
            'slug' => 'sitio1',
        ]);

        $this->coupon = Coupon::factory()->create([
            'site_id' => $this->site->id,
            'code' => 'TESTCOUPON',
            'is_active' => true,
            'qr_enabled' => true,
            'qr_token' => 'ofwulemdeiesnlfw9vxuqejle1gunegxqzhggctvzf4orbxq',
            'used_count' => 0,
            'max_uses' => null,
            'valid_from' => null,
            'valid_to' => null,
        ]);
    }

    public function test_qr_redeem_resolves_token_parameter_from_path(): void
    {
        // Verify coupon exists in DB with expected attributes
        $dbCoupon = Coupon::query()
            ->where('site_id', $this->site->id)
            ->where('qr_enabled', true)
            ->where('qr_token', 'ofwulemdeiesnlfw9vxuqejle1gunegxqzhggctvzf4orbxq')
            ->first();
        $this->assertNotNull($dbCoupon, 'Coupon should exist in DB before request');
        $this->assertEquals('ofwulemdeiesnlfw9vxuqejle1gunegxqzhggctvzf4orbxq', $dbCoupon->qr_token);
        $this->assertTrue($dbCoupon->qr_enabled);
        $this->assertEquals($this->site->id, $dbCoupon->site_id);

        $response = $this->get('https://sitio1.klivip.test/cupones/qr/ofwulemdeiesnlfw9vxuqejle1gunegxqzhggctvzf4orbxq');

        // Debug: dump response content if not 200
        if ($response->status() !== 200) {
            dump([
                'status' => $response->status(),
                'route_params' => $response->baseRequest->route()?->parameters(),
                'coupon_in_db_after_request' => Coupon::query()->where('qr_token', 'ofwulemdeiesnlfw9vxuqejle1gunegxqzhggctvzf4orbxq')->first(),
                'all_coupons' => Coupon::all()->toArray(),
                'all_sites' => Site::all()->toArray(),
            ]);
        }

        $response->assertStatus(200);
        $response->assertViewHas('status', 'redeemed');
        $response->assertViewHas('couponCode', 'TESTCOUPON');
    }

    public function test_qr_redeem_does_not_use_site_slug_as_token(): void
    {
        $response = $this->get('https://sitio1.klivip.test/cupones/qr/sitio1');

        $response->assertStatus(404);
        $response->assertViewHas('status', 'not-found');
    }

    public function test_qr_redeem_returns_not_found_for_invalid_token(): void
    {
        $response = $this->get('https://sitio1.klivip.test/cupones/qr/invalidtoken');

        $response->assertStatus(404);
        $response->assertViewHas('status', 'not-found');
    }

    public function test_qr_redeem_returns_not_found_for_coupon_on_different_site(): void
    {
        $otherSite = Site::factory()->create([
            'slug' => 'sitio2',
        ]);

        $response = $this->get('https://sitio2.klivip.test/cupones/qr/ofwulemdeiesnlfw9vxuqejle1gunegxqzhggctvzf4orbxq');

        $response->assertStatus(404);
        $response->assertViewHas('status', 'not-found');
    }

    public function test_qr_redeem_returns_invalid_for_inactive_coupon(): void
    {
        $this->coupon->update(['is_active' => false]);

        $response = $this->get('https://sitio1.klivip.test/cupones/qr/ofwulemdeiesnlfw9vxuqejle1gunegxqzhggctvzf4orbxq');

        $response->assertStatus(422);
        $response->assertViewHas('status', 'invalid');
    }

    public function test_qr_redeem_returns_invalid_for_expired_coupon(): void
    {
        $this->coupon->update(['valid_to' => now()->subDay()]);

        $response = $this->get('https://sitio1.klivip.test/cupones/qr/ofwulemdeiesnlfw9vxuqejle1gunegxqzhggctvzf4orbxq');

        $response->assertStatus(422);
        $response->assertViewHas('status', 'invalid');
    }

    public function test_qr_redeem_returns_invalid_for_max_uses_reached(): void
    {
        $this->coupon->update(['max_uses' => 1, 'used_count' => 1]);

        $response = $this->get('https://sitio1.klivip.test/cupones/qr/ofwulemdeiesnlfw9vxuqejle1gunegxqzhggctvzf4orbxq');

        $response->assertStatus(422);
        $response->assertViewHas('status', 'invalid');
    }

    public function test_qr_redeem_increments_used_count(): void
    {
        $this->assertEquals(0, $this->coupon->used_count);

        $this->get('https://sitio1.klivip.test/cupones/qr/ofwulemdeiesnlfw9vxuqejle1gunegxqzhggctvzf4orbxq');

        $this->coupon->refresh();
        $this->assertEquals(1, $this->coupon->used_count);
    }

    public function test_qr_redeem_on_cloud_domain(): void
    {
        $response = $this->get('https://sitio1.klivip.cloud/cupones/qr/ofwulemdeiesnlfw9vxuqejle1gunegxqzhggctvzf4orbxq');

        $response->assertStatus(200);
        $response->assertViewHas('status', 'redeemed');
        $response->assertViewHas('couponCode', 'TESTCOUPON');
    }
}
