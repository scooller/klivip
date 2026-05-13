<?php

namespace Tests\Feature;

use App\Enums\PromotionScheduleType;
use App\Enums\PromotionScope;
use App\Enums\UserRole;
use App\Models\Promotion;
use App\Models\Site;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Site, 1: Site, 2: Promotion, 3: Promotion, 4: Promotion}
     */
    private function createPromotionFixtures(): array
    {
        $siteA = Site::factory()->create();
        $siteB = Site::factory()->create();

        $globalPromotion = Promotion::factory()->create([
            'site_id' => null,
            'scope' => PromotionScope::Global,
            'title' => 'Global 2x1',
        ]);
        $sitePromotionA = Promotion::factory()->create([
            'site_id' => $siteA->id,
            'scope' => PromotionScope::Site,
            'title' => 'Jueves Pizza A',
        ]);
        $sitePromotionB = Promotion::factory()->create([
            'site_id' => $siteB->id,
            'scope' => PromotionScope::Site,
            'title' => 'Viernes 2x1 B',
        ]);

        return [$siteA, $siteB, $globalPromotion, $sitePromotionA, $sitePromotionB];
    }

    public function test_super_admin_can_manage_all_promotions(): void
    {
        [,, $globalPromotion, $sitePromotionA, $sitePromotionB] = $this->createPromotionFixtures();

        $superAdmin = User::factory()->superAdmin()->create();

        $this->assertTrue($superAdmin->can('viewAny', Promotion::class));
        $this->assertTrue($superAdmin->can('view', $globalPromotion));
        $this->assertTrue($superAdmin->can('update', $globalPromotion));
        $this->assertTrue($superAdmin->can('delete', $globalPromotion));
        $this->assertTrue($superAdmin->can('view', $sitePromotionA));
        $this->assertTrue($superAdmin->can('update', $sitePromotionA));
        $this->assertTrue($superAdmin->can('delete', $sitePromotionA));
        $this->assertTrue($superAdmin->can('view', $sitePromotionB));
    }

    public function test_admin_can_manage_only_assigned_site_promotions_and_view_globals(): void
    {
        [$siteA,, $globalPromotion, $sitePromotionA, $sitePromotionB] = $this->createPromotionFixtures();

        $admin = User::factory()->admin()->create();
        $admin->sites()->attach($siteA);

        $this->assertTrue($admin->can('viewAny', Promotion::class));
        $this->assertTrue($admin->can('create', Promotion::class));
        $this->assertTrue($admin->can('view', $globalPromotion));
        $this->assertFalse($admin->can('update', $globalPromotion));
        $this->assertFalse($admin->can('delete', $globalPromotion));
        $this->assertTrue($admin->can('view', $sitePromotionA));
        $this->assertTrue($admin->can('update', $sitePromotionA));
        $this->assertTrue($admin->can('delete', $sitePromotionA));
        $this->assertFalse($admin->can('view', $sitePromotionB));
        $this->assertFalse($admin->can('update', $sitePromotionB));
        $this->assertFalse($admin->can('delete', $sitePromotionB));
    }

    public function test_manager_cannot_delete_promotions_even_in_assigned_sites(): void
    {
        [$siteA,, $globalPromotion, $sitePromotionA] = $this->createPromotionFixtures();

        $manager = User::factory()->manager()->create();
        $manager->sites()->attach($siteA);

        $this->assertTrue($manager->can('viewAny', Promotion::class));
        $this->assertTrue($manager->can('create', Promotion::class));
        $this->assertTrue($manager->can('view', $globalPromotion));
        $this->assertFalse($manager->can('update', $globalPromotion));
        $this->assertTrue($manager->can('view', $sitePromotionA));
        $this->assertTrue($manager->can('update', $sitePromotionA));
        $this->assertFalse($manager->can('delete', $sitePromotionA));
    }

    public function test_regular_user_cannot_access_promotion_management(): void
    {
        [,, $globalPromotion, $sitePromotionA] = $this->createPromotionFixtures();

        $user = User::factory()->create([
            'role' => UserRole::User,
        ]);

        $this->assertFalse($user->can('viewAny', Promotion::class));
        $this->assertFalse($user->can('create', Promotion::class));
        $this->assertFalse($user->can('view', $globalPromotion));
        $this->assertFalse($user->can('view', $sitePromotionA));
        $this->assertFalse($user->can('update', $sitePromotionA));
        $this->assertFalse($user->can('delete', $sitePromotionA));
    }

    public function test_recurring_promotion_matches_day_and_time_window(): void
    {
        $promotion = Promotion::factory()->create([
            'schedule_type' => PromotionScheduleType::Recurrent,
            'recurrent_days' => [4],
            'start_time' => '10:00:00',
            'end_time' => '22:00:00',
        ]);

        $thursdayAtNoon = CarbonImmutable::parse('2026-05-14 12:00:00');
        $thursdayEarly = CarbonImmutable::parse('2026-05-14 08:00:00');
        $fridayAtNoon = CarbonImmutable::parse('2026-05-15 12:00:00');

        $this->assertTrue($promotion->isScheduledFor($thursdayAtNoon));
        $this->assertFalse($promotion->isScheduledFor($thursdayEarly));
        $this->assertFalse($promotion->isScheduledFor($fridayAtNoon));
    }

    public function test_special_promotion_matches_only_its_date(): void
    {
        $promotion = Promotion::factory()->create([
            'schedule_type' => PromotionScheduleType::Special,
            'special_date' => '2026-05-15',
        ]);

        $this->assertTrue($promotion->isScheduledFor(CarbonImmutable::parse('2026-05-15 20:00:00')));
        $this->assertFalse($promotion->isScheduledFor(CarbonImmutable::parse('2026-05-16 20:00:00')));
    }
}
