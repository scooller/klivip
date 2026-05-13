<?php

namespace Database\Seeders;

use App\Enums\CouponType;
use App\Enums\PromotionScheduleType;
use App\Enums\PromotionScope;
use App\Enums\UserRole;
use App\Models\Coupon;
use App\Models\Game;
use App\Models\Promotion;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $siteOne = Site::query()->firstWhere('slug', 'sitio1');
        $siteTwo = Site::query()->firstWhere('slug', 'sitio2');

        if (! $siteOne || ! $siteTwo) {
            return;
        }

        $siteOne->update([
            'content' => '<h2>Bienvenido a Sitio 1</h2><p>Contenido de prueba editable con WYSIWYG.</p>',
            'address' => 'Av. Principal 123, Ciudad Demo',
            'opening_hours' => 'Lun-Dom 10:00 - 22:00',
        ]);

        $siteTwo->update([
            'content' => '<h2>Bienvenido a Sitio 2</h2><p>Landing de ejemplo con promociones y juegos.</p>',
            'address' => 'Calle Central 456, Ciudad Demo',
            'opening_hours' => 'Lun-Sab 09:00 - 21:00',
        ]);

        $admin = User::query()->updateOrCreate(
            ['email' => 'site-admin@klivip.test'],
            [
                'name' => 'Site Admin',
                'password' => bcrypt('password'),
                'role' => UserRole::Admin,
            ],
        );

        $manager = User::query()->updateOrCreate(
            ['email' => 'manager@klivip.test'],
            [
                'name' => 'Site Manager',
                'password' => bcrypt('password'),
                'role' => UserRole::Manager,
            ],
        );

        $user = User::query()->updateOrCreate(
            ['email' => 'user@klivip.test'],
            [
                'name' => 'Regular User',
                'password' => bcrypt('password'),
                'role' => UserRole::User,
            ],
        );

        $admin->sites()->sync([$siteOne->id, $siteTwo->id]);
        $manager->sites()->sync([$siteOne->id]);
        $user->sites()->sync([$siteOne->id]);

        Coupon::query()->updateOrCreate(
            ['site_id' => $siteOne->id, 'code' => 'PIZZA10'],
            [
                'type' => CouponType::Percentage,
                'value' => 10,
                'max_uses' => 200,
                'used_count' => 15,
                'valid_from' => now()->subDays(3),
                'valid_to' => now()->addDays(30),
                'is_active' => true,
            ],
        );

        Coupon::query()->updateOrCreate(
            ['site_id' => $siteTwo->id, 'code' => '2X1VIERNES'],
            [
                'type' => CouponType::Fixed,
                'value' => 20,
                'max_uses' => 120,
                'used_count' => 3,
                'valid_from' => now()->subDay(),
                'valid_to' => now()->addDays(10),
                'is_active' => true,
            ],
        );

        Promotion::query()->updateOrCreate(
            ['title' => 'Jueves de Pizza', 'site_id' => $siteOne->id],
            [
                'offer_label' => 'PIZZA10',
                'description' => 'Promo recurrente todos los jueves',
                'scope' => PromotionScope::Site,
                'schedule_type' => PromotionScheduleType::Recurrent,
                'recurrent_days' => [4],
                'special_date' => null,
                'starts_at' => now()->subWeek(),
                'ends_at' => now()->addMonths(2),
                'start_time' => '10:00:00',
                'end_time' => '22:00:00',
                'is_active' => true,
            ],
        );

        Promotion::query()->updateOrCreate(
            ['title' => 'Viernes 2x1', 'site_id' => $siteTwo->id],
            [
                'offer_label' => '2X1VIERNES',
                'description' => 'Promo especial de viernes',
                'scope' => PromotionScope::Site,
                'schedule_type' => PromotionScheduleType::Special,
                'recurrent_days' => null,
                'special_date' => now()->addDays(2)->toDateString(),
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addDays(5),
                'start_time' => null,
                'end_time' => null,
                'is_active' => true,
            ],
        );

        Promotion::query()->updateOrCreate(
            ['title' => 'Promo Global Bienvenida', 'site_id' => null],
            [
                'offer_label' => 'WELCOME',
                'description' => 'Promocion disponible para todos los sitios',
                'scope' => PromotionScope::Global,
                'schedule_type' => PromotionScheduleType::Standard,
                'recurrent_days' => null,
                'special_date' => null,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addMonth(),
                'start_time' => null,
                'end_time' => null,
                'is_active' => true,
            ],
        );

        $ruleta = Game::query()->updateOrCreate(
            ['title' => 'Ruleta Clásica'],
            [
                'url' => 'https://games.example.com/ruleta-clasica',
                'description' => 'Juego destacado para pruebas en Sitio 1.',
                'sort_order' => 1,
                'is_active' => true,
                'is_featured' => true,
            ],
        );

        $blackjack = Game::query()->updateOrCreate(
            ['title' => 'Blackjack Pro'],
            [
                'url' => 'https://games.example.com/blackjack-pro',
                'description' => 'Juego de cartas vinculado a la página.',
                'sort_order' => 2,
                'is_active' => true,
                'is_featured' => false,
            ],
        );

        $slots = Game::query()->updateOrCreate(
            ['title' => 'Slots Neon'],
            [
                'url' => 'https://games.example.com/slots-neon',
                'description' => 'Juego principal de Sitio 2.',
                'sort_order' => 3,
                'is_active' => true,
                'is_featured' => true,
            ],
        );

        $poker = Game::query()->updateOrCreate(
            ['title' => 'Poker Texas Hold\'em'],
            [
                'url' => 'https://games.example.com/poker-texas',
                'description' => 'Variante clásica de poker para todos los sitios.',
                'sort_order' => 4,
                'is_active' => true,
                'is_featured' => false,
            ],
        );

        $siteOne->games()->sync([
            $ruleta->id => ['sort_order' => 1],
            $blackjack->id => ['sort_order' => 2],
            $poker->id => ['sort_order' => 3],
        ]);

        $siteTwo->games()->sync([
            $slots->id => ['sort_order' => 1],
            $ruleta->id => ['sort_order' => 2],
            $poker->id => ['sort_order' => 3],
        ]);
    }
}
