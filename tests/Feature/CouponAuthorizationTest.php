<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Coupon;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Site, 1: Site, 2: Coupon, 3: Coupon}
     */
    private function createCouponFixtures(): array
    {
        $siteA = Site::factory()->create();
        $siteB = Site::factory()->create();

        $couponA = Coupon::factory()->create([
            'site_id' => $siteA->id,
            'code' => 'WELCOME10',
        ]);
        $couponB = Coupon::factory()->create([
            'site_id' => $siteB->id,
            'code' => 'WELCOME20',
        ]);

        return [$siteA, $siteB, $couponA, $couponB];
    }

    public function test_super_admin_can_manage_all_coupons(): void
    {
        [,, $couponA, $couponB] = $this->createCouponFixtures();

        $superAdmin = User::factory()->superAdmin()->create();

        $this->assertTrue($superAdmin->can('viewAny', Coupon::class));
        $this->assertTrue($superAdmin->can('view', $couponA));
        $this->assertTrue($superAdmin->can('view', $couponB));
        $this->assertTrue($superAdmin->can('create', Coupon::class));
        $this->assertTrue($superAdmin->can('update', $couponA));
        $this->assertTrue($superAdmin->can('delete', $couponA));
    }

    public function test_admin_can_only_manage_coupons_in_assigned_sites(): void
    {
        [$siteA,, $couponA, $couponB] = $this->createCouponFixtures();

        $admin = User::factory()->admin()->create();
        $admin->sites()->attach($siteA);

        $this->assertTrue($admin->can('viewAny', Coupon::class));
        $this->assertTrue($admin->can('create', Coupon::class));
        $this->assertTrue($admin->can('view', $couponA));
        $this->assertTrue($admin->can('update', $couponA));
        $this->assertTrue($admin->can('delete', $couponA));
        $this->assertFalse($admin->can('view', $couponB));
        $this->assertFalse($admin->can('update', $couponB));
        $this->assertFalse($admin->can('delete', $couponB));
    }

    public function test_manager_cannot_delete_coupons_even_in_assigned_sites(): void
    {
        [$siteA,, $couponA] = $this->createCouponFixtures();

        $manager = User::factory()->manager()->create();
        $manager->sites()->attach($siteA);

        $this->assertTrue($manager->can('viewAny', Coupon::class));
        $this->assertTrue($manager->can('view', $couponA));
        $this->assertTrue($manager->can('update', $couponA));
        $this->assertFalse($manager->can('delete', $couponA));
    }

    public function test_regular_user_cannot_access_coupon_management(): void
    {
        [,, $couponA] = $this->createCouponFixtures();

        $user = User::factory()->create([
            'role' => UserRole::User,
        ]);

        $this->assertFalse($user->can('viewAny', Coupon::class));
        $this->assertFalse($user->can('create', Coupon::class));
        $this->assertFalse($user->can('view', $couponA));
        $this->assertFalse($user->can('update', $couponA));
        $this->assertFalse($user->can('delete', $couponA));
    }

    public function test_coupon_code_is_unique_per_site(): void
    {
        $site = Site::factory()->create();

        Coupon::factory()->create([
            'site_id' => $site->id,
            'code' => 'SUMMER10',
        ]);

        $this->expectException(QueryException::class);

        Coupon::factory()->create([
            'site_id' => $site->id,
            'code' => 'SUMMER10',
        ]);
    }

    public function test_coupon_code_can_repeat_in_different_sites(): void
    {
        $siteA = Site::factory()->create();
        $siteB = Site::factory()->create();

        Coupon::factory()->create([
            'site_id' => $siteA->id,
            'code' => 'VIP50',
        ]);

        Coupon::factory()->create([
            'site_id' => $siteB->id,
            'code' => 'VIP50',
        ]);

        $this->assertEquals(2, Coupon::query()->where('code', 'VIP50')->count());
    }
}
