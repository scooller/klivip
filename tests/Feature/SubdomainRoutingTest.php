<?php

namespace Tests\Feature;

use App\Enums\PromotionScope;
use App\Models\Game;
use App\Models\Promotion;
use App\Models\Site;
use App\Models\SiteSetting;
use App\Models\User;
use App\Notifications\FrontCustomerOtpNotification;
use App\Notifications\FrontProfileUnlockOtpNotification;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
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

        $response = $this->get('http://sitio1.klivip.test/cuenta');

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

        $response = $this->get('http://sitio-promo.klivip.test/cuenta');

        $response->assertOk();
        $response->assertSee('"component":"User"', false);
        $response->assertSee('Sitio Promo');
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

    public function test_customer_can_request_and_verify_otp_login_on_front(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);
        Notification::fake();

        Site::query()->create([
            'name' => 'Sitio Login',
            'slug' => 'sitio-login',
            'is_active' => true,
        ]);

        $customer = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => 'password',
        ]);

        $requestResponse = $this->from('http://sitio-login.klivip.test/usuario')->post('http://sitio-login.klivip.test/usuario/login', [
            'identifier' => 'customer@example.com',
            'remember' => true,
        ]);

        $requestResponse->assertRedirect('http://sitio-login.klivip.test/usuario');

        $otpCode = null;

        Notification::assertSentTo($customer, FrontCustomerOtpNotification::class, function ($notification) use (&$otpCode): bool {
            $otpCode = $notification->code;

            return true;
        });

        $this->assertNotNull($otpCode);

        $verifyResponse = $this->from('http://sitio-login.klivip.test/usuario')->post('http://sitio-login.klivip.test/usuario/login/verify', [
            'identifier' => 'customer@example.com',
            'otp_code' => $otpCode,
            'remember' => true,
        ]);

        $verifyResponse->assertRedirect('http://sitio-login.klivip.test/');
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
            'identifier' => 'admin@example.com',
        ]);

        $response->assertRedirect('http://sitio-login.klivip.test/usuario');
        $response->assertSessionHasErrors('identifier');
        $this->assertGuest('customer');
    }

    public function test_customer_can_login_without_otp_when_setting_is_enabled(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        Site::query()->create([
            'name' => 'Sitio Login',
            'slug' => 'sitio-login',
            'is_active' => true,
        ]);

        SiteSetting::current()->update([
            'enable_home_login_without_code' => true,
        ]);

        $customer = User::factory()->create([
            'email' => 'direct@example.com',
        ]);

        $response = $this->post('http://sitio-login.klivip.test/usuario/login', [
            'identifier' => 'direct@example.com',
        ]);

        $response->assertRedirect('http://sitio-login.klivip.test/');
        $this->assertAuthenticatedAs($customer, 'customer');
    }

    public function test_customer_cannot_update_profile_without_unlocking_first(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        Site::query()->create([
            'name' => 'Sitio Perfil',
            'slug' => 'sitio-perfil',
            'is_active' => true,
        ]);

        $customer = User::factory()->create([
            'email' => 'locked@example.com',
            'birth_date' => now()->subYears(25)->toDateString(),
        ]);

        $this->actingAs($customer, 'customer');

        $response = $this->from('http://sitio-perfil.klivip.test/usuario')->post('http://sitio-perfil.klivip.test/usuario/perfil', [
            'name' => 'Nuevo Nombre',
            'email' => 'locked@example.com',
            'email_confirmation' => 'locked@example.com',
            'phone' => '+56 9 1111 2222',
            'birth_date' => now()->subYears(25)->toDateString(),
        ]);

        $response->assertRedirect('http://sitio-perfil.klivip.test/usuario');
        $response->assertSessionHasErrors('profile');
    }

    public function test_customer_can_unlock_profile_with_otp_and_then_update(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);
        Notification::fake();

        Site::query()->create([
            'name' => 'Sitio Perfil',
            'slug' => 'sitio-perfil',
            'is_active' => true,
        ]);

        $customer = User::factory()->create([
            'email' => 'customer@laravel.com',
            'birth_date' => now()->subYears(24)->toDateString(),
        ]);

        $this->actingAs($customer, 'customer');

        $requestUnlockResponse = $this->from('http://sitio-perfil.klivip.test/usuario')->post('http://sitio-perfil.klivip.test/usuario/perfil/unlock/otp/request');
        $requestUnlockResponse->assertRedirect('http://sitio-perfil.klivip.test/usuario');

        $otpCode = null;

        Notification::assertSentTo($customer, FrontProfileUnlockOtpNotification::class, function ($notification) use (&$otpCode): bool {
            $otpCode = $notification->code;

            return true;
        });

        $this->assertNotNull($otpCode);

        $verifyResponse = $this->from('http://sitio-perfil.klivip.test/usuario')->post('http://sitio-perfil.klivip.test/usuario/perfil/unlock/otp/verify', [
            'otp_code' => $otpCode,
        ]);
        $verifyResponse->assertRedirect('http://sitio-perfil.klivip.test/usuario');
        $verifyResponse->assertSessionHasNoErrors();

        $updateResponse = $this->from('http://sitio-perfil.klivip.test/usuario')->post('http://sitio-perfil.klivip.test/usuario/perfil', [
            'name' => 'Perfil Desbloqueado',
            'email' => 'customer@laravel.com',
            'email_confirmation' => 'customer@laravel.com',
            'phone' => '+56 9 1234 5678',
            'birth_date' => now()->subYears(24)->toDateString(),
        ]);

        $updateResponse->assertRedirect('http://sitio-perfil.klivip.test/usuario');
        $updateResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', [
            'id' => $customer->id,
            'name' => 'Perfil Desbloqueado',
        ]);
    }
}
