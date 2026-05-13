<?php

namespace Tests\Feature;

use App\Enums\PromotionScope;
use App\Models\Game;
use App\Models\Promotion;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubdomainRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_a_valid_site_subdomain(): void
    {
        $this->withoutVite();

        Site::query()->create([
            'name' => 'Sitio 1',
            'slug' => 'sitio1',
            'is_active' => true,
        ]);

        $response = $this->get('http://sitio1.klivip.test/');

        $response->assertOk();
        $response->assertSee('Sitio 1');
    }

    public function test_it_returns_404_for_missing_subdomain_site(): void
    {
        $response = $this->get('http://inexistente.klivip.test/');

        $response->assertNotFound();
    }

    public function test_it_renders_site_related_promotions_and_games(): void
    {
        $this->withoutVite();

        $site = Site::query()->create([
            'name' => 'Sitio Promo',
            'slug' => 'sitio-promo',
            'is_active' => true,
        ]);

        Promotion::factory()->create([
            'site_id' => $site->id,
            'scope' => PromotionScope::Site,
            'offer_label' => 'Promo 2x1',
            'title' => 'Promo del sitio',
            'is_active' => true,
        ]);

        $game = Game::factory()->featured()->create([
            'title' => 'Ruleta VIP',
            'is_active' => true,
        ]);

        $site->games()->attach($game->id, ['sort_order' => 1]);

        $response = $this->get('http://sitio-promo.klivip.test/');

        $response->assertOk();
        $response->assertSee('Promo 2x1');
        $response->assertSee('Ruleta VIP');
    }

    public function test_it_renders_user_page_for_a_valid_site_subdomain(): void
    {
        $this->withoutVite();

        Site::query()->create([
            'name' => 'Sitio Usuario',
            'slug' => 'sitio-usuario',
            'is_active' => true,
        ]);

        $response = $this->get('http://sitio-usuario.klivip.test/usuario');

        $response->assertOk();
        $response->assertSee('"component":"User"', false);
        $response->assertSee('Sitio Usuario');
    }

    public function test_customer_can_login_on_front_using_customer_guard(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        Site::query()->create([
            'name' => 'Sitio Login',
            'slug' => 'sitio-login',
            'is_active' => true,
        ]);

        $customer = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => 'password',
        ]);

        $response = $this->post('http://sitio-login.klivip.test/usuario/login', [
            'email' => 'customer@example.com',
            'password' => 'password',
            'remember' => true,
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($customer, 'customer');
    }

    public function test_admin_cannot_login_as_customer_on_front(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        Site::query()->create([
            'name' => 'Sitio Login',
            'slug' => 'sitio-login',
            'is_active' => true,
        ]);

        User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response = $this->from('http://sitio-login.klivip.test/usuario')->post('http://sitio-login.klivip.test/usuario/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('http://sitio-login.klivip.test/usuario');
        $response->assertSessionHasErrors('email');
        $this->assertGuest('customer');
    }
}
