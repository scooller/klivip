<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Game, 1: Game}
     */
    private function createGameFixtures(): array
    {
        $activeGame = Game::factory()->create([
            'title' => 'Ruleta Clásica',
            'is_active' => true,
            'is_featured' => true,
        ]);

        $inactiveGame = Game::factory()->inactive()->create([
            'title' => 'Juego Inactivo',
        ]);

        return [$activeGame, $inactiveGame];
    }

    public function test_super_admin_can_manage_all_games(): void
    {
        [$activeGame, $inactiveGame] = $this->createGameFixtures();

        $superAdmin = User::factory()->superAdmin()->create();

        $this->assertTrue($superAdmin->can('viewAny', Game::class));
        $this->assertTrue($superAdmin->can('view', $activeGame));
        $this->assertTrue($superAdmin->can('view', $inactiveGame));
        $this->assertTrue($superAdmin->can('create', Game::class));
        $this->assertTrue($superAdmin->can('update', $activeGame));
        $this->assertTrue($superAdmin->can('delete', $activeGame));
    }

    public function test_admin_can_view_and_edit_games_but_not_delete(): void
    {
        [$activeGame, $inactiveGame] = $this->createGameFixtures();

        $admin = User::factory()->admin()->create();

        $this->assertTrue($admin->can('viewAny', Game::class));
        $this->assertTrue($admin->can('view', $activeGame));
        $this->assertTrue($admin->can('view', $inactiveGame));
        $this->assertTrue($admin->can('create', Game::class));
        $this->assertTrue($admin->can('update', $activeGame));
        $this->assertFalse($admin->can('delete', $activeGame));
    }

    public function test_manager_can_view_games_but_not_create_or_edit(): void
    {
        [$activeGame] = $this->createGameFixtures();

        $manager = User::factory()->manager()->create();

        $this->assertTrue($manager->can('viewAny', Game::class));
        $this->assertTrue($manager->can('view', $activeGame));
        $this->assertFalse($manager->can('create', Game::class));
        $this->assertFalse($manager->can('update', $activeGame));
        $this->assertFalse($manager->can('delete', $activeGame));
    }

    public function test_regular_user_cannot_access_games(): void
    {
        [$activeGame] = $this->createGameFixtures();

        $user = User::factory()->create();

        $this->assertFalse($user->can('viewAny', Game::class));
        $this->assertFalse($user->can('view', $activeGame));
        $this->assertFalse($user->can('create', Game::class));
        $this->assertFalse($user->can('update', $activeGame));
        $this->assertFalse($user->can('delete', $activeGame));
    }

    public function test_game_factory_featured_state_sets_is_featured(): void
    {
        $game = Game::factory()->featured()->create();

        $this->assertTrue($game->is_featured);
        $this->assertTrue($game->is_active);
    }

    public function test_game_factory_inactive_state_sets_is_active_false(): void
    {
        $game = Game::factory()->inactive()->create();

        $this->assertFalse($game->is_active);
    }

    public function test_game_can_be_attached_to_site_with_pivot_sort_order(): void
    {
        $site = Site::factory()->create();
        $game = Game::factory()->featured()->create();

        $site->games()->attach($game->id, ['sort_order' => 5]);

        $pivotGame = $site->games()->first();

        $this->assertNotNull($pivotGame);
        $this->assertSame(5, $pivotGame->pivot->sort_order);
        $this->assertTrue($pivotGame->is_featured);
    }

    public function test_each_site_can_have_different_sort_order_for_same_game(): void
    {
        $siteA = Site::factory()->create();
        $siteB = Site::factory()->create();
        $game = Game::factory()->create();

        $siteA->games()->attach($game->id, ['sort_order' => 1]);
        $siteB->games()->attach($game->id, ['sort_order' => 9]);

        $gameInSiteA = $siteA->games()->first();
        $gameInSiteB = $siteB->games()->first();

        $this->assertSame(1, $gameInSiteA->pivot->sort_order);
        $this->assertSame(9, $gameInSiteB->pivot->sort_order);
    }
}
