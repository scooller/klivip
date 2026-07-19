<?php

namespace Tests\Feature;

use App\Jobs\NotifySweepstakeWinnersJob;
use App\Models\CouponRedemption;
use App\Models\Site;
use App\Models\Sweepstake;
use App\Models\SweepstakeCoupon;
use App\Models\SweepstakeDraw;
use App\Models\User;
use App\Services\SmsService;
use App\Services\SweepstakeDrawService;
use FinityLabs\FinMail\Mail\TemplateMail;
use FinityLabs\FinMail\Models\EmailTemplate;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class SweepstakeDrawTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Crea una sweepstake con N cupones válidos, cada uno asociado a un user distinto.
     *
     * @return array{0: Sweepstake, 1: Collection<int, SweepstakeCoupon>}
     */
    private function createSweepstakeWithCoupons(int $couponCount, array $userAttrs = []): array
    {
        $site = Site::factory()->create();
        $sweepstake = Sweepstake::factory()->create([
            'site_id' => $site->id,
            'last_coupon_number' => 0,
        ]);

        $coupons = collect();
        for ($i = 1; $i <= $couponCount; $i++) {
            $user = User::factory()->create(array_merge([
                'email' => "user{$i}@example.com",
                'phone' => '+1555000'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
            ], $userAttrs));

            $redemption = CouponRedemption::create([
                'sweepstake_id' => $sweepstake->id,
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_phone' => $user->phone,
                'user_name' => $user->name,
                'coupon_count' => 1,
                'coupon_start_number' => $i,
                'coupon_end_number' => $i,
                'is_voided' => false,
            ]);

            $coupons->push(SweepstakeCoupon::create([
                'sweepstake_id' => $sweepstake->id,
                'redemption_id' => $redemption->id,
                'user_id' => $user->id,
                'coupon_number' => $i,
                'is_voided' => false,
                'is_used' => false,
            ]));
        }

        return [$sweepstake, $coupons];
    }

    /**
     * Crea cupones sin usuario (cupones manuales).
     *
     * @return Collection<int, SweepstakeCoupon>
     */
    private function createAnonymousCoupons(Sweepstake $sweepstake, int $count): Collection
    {
        $start = SweepstakeCoupon::withTrashed()->where('sweepstake_id', $sweepstake->id)->max('coupon_number') ?? 0;

        $redemption = CouponRedemption::create([
            'sweepstake_id' => $sweepstake->id,
            'user_id' => null,
            'coupon_count' => $count,
            'coupon_start_number' => $start + 1,
            'coupon_end_number' => $start + $count,
            'is_voided' => false,
        ]);

        return collect(range(1, $count))->map(function (int $i) use ($sweepstake, $redemption, $start): SweepstakeCoupon {
            return SweepstakeCoupon::create([
                'sweepstake_id' => $sweepstake->id,
                'redemption_id' => $redemption->id,
                'user_id' => null,
                'coupon_number' => $start + $i,
                'is_voided' => false,
                'is_used' => false,
            ]);
        });
    }

    // ==========================================
    // Service: SweepstakeDrawService::draw()
    // ==========================================

    public function test_draw_selects_winners_and_marks_coupons_as_used(): void
    {
        [$sweepstake, $coupons] = $this->createSweepstakeWithCoupons(10);
        $drawnBy = User::factory()->create();

        $service = app(SweepstakeDrawService::class);
        $draw = $service->draw($sweepstake, 3, $drawnBy, 'Sorteo público');

        $this->assertDatabaseHas('sweepstake_draws', [
            'sweepstake_id' => $sweepstake->id,
            'winners_count' => 3,
            'drawn_by' => $drawnBy->id,
            'notified' => false,
        ]);

        // 3 pivot rows with unique positions 1..3
        $winners = $draw->winners()->orderByPivot('position')->get();
        $this->assertCount(3, $winners);
        $this->assertSame([1, 2, 3], $winners->pluck('pivot.position')->all());

        // Winners must be marked as used
        $winners->each(function (SweepstakeCoupon $coupon) use ($drawnBy) {
            $this->assertTrue($coupon->fresh()->is_used);
            $this->assertNotNull($coupon->fresh()->used_at);
            $this->assertSame($drawnBy->id, $coupon->fresh()->used_by);
        });

        // Non-winning coupons must remain unused
        $nonWinners = $coupons->whereNotIn('id', $winners->pluck('id'));
        $nonWinners->each(fn (SweepstakeCoupon $c) => $this->assertFalse($c->fresh()->is_used));
    }

    public function test_draw_with_one_winner_selects_single_coupon(): void
    {
        [$sweepstake] = $this->createSweepstakeWithCoupons(5);

        $draw = app(SweepstakeDrawService::class)->draw($sweepstake, 1);

        $this->assertCount(1, $draw->winners);
        $this->assertSame(1, $draw->winners_count);
        $this->assertSame(1, (int) $draw->winners->first()->pivot->position);
    }

    public function test_draw_with_all_coupons_as_winners(): void
    {
        [$sweepstake, $coupons] = $this->createSweepstakeWithCoupons(4);

        $draw = app(SweepstakeDrawService::class)->draw($sweepstake, 4);

        $this->assertSame(4, $draw->winners_count);
        $this->assertCount(4, $draw->winners);
        // Todos los cupones deben estar marcados como usados
        $coupons->each(fn (SweepstakeCoupon $c) => $this->assertTrue($c->fresh()->is_used));
    }

    public function test_draw_throws_when_not_enough_coupons(): void
    {
        [$sweepstake] = $this->createSweepstakeWithCoupons(2);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No hay cupones suficientes');

        app(SweepstakeDrawService::class)->draw($sweepstake, 5);
    }

    public function test_draw_throws_when_winners_count_below_one(): void
    {
        [$sweepstake] = $this->createSweepstakeWithCoupons(5);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('mayor o igual a 1');

        app(SweepstakeDrawService::class)->draw($sweepstake, 0);
    }

    public function test_draw_throws_when_winners_count_is_negative(): void
    {
        [$sweepstake] = $this->createSweepstakeWithCoupons(5);

        $this->expectException(RuntimeException::class);

        app(SweepstakeDrawService::class)->draw($sweepstake, -3);
    }

    public function test_draw_throws_when_no_coupons_available(): void
    {
        $site = Site::factory()->create();
        $sweepstake = Sweepstake::factory()->create(['site_id' => $site->id]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No hay cupones suficientes');

        app(SweepstakeDrawService::class)->draw($sweepstake, 1);
    }

    public function test_draw_excludes_voided_coupons(): void
    {
        $site = Site::factory()->create();
        $sweepstake = Sweepstake::factory()->create(['site_id' => $site->id]);

        $redemption = CouponRedemption::create([
            'sweepstake_id' => $sweepstake->id,
            'user_id' => User::factory()->create()->id,
            'coupon_count' => 2,
            'coupon_start_number' => 1,
            'coupon_end_number' => 2,
            'is_voided' => false,
        ]);

        $valid = SweepstakeCoupon::create([
            'sweepstake_id' => $sweepstake->id,
            'redemption_id' => $redemption->id,
            'user_id' => $redemption->user_id,
            'coupon_number' => 1,
            'is_voided' => false,
        ]);

        SweepstakeCoupon::create([
            'sweepstake_id' => $sweepstake->id,
            'redemption_id' => $redemption->id,
            'user_id' => $redemption->user_id,
            'coupon_number' => 2,
            'is_voided' => true,
        ]);

        $eligible = app(SweepstakeDrawService::class)->getEligibleCoupons($sweepstake);

        $this->assertCount(1, $eligible);
        $this->assertSame($valid->id, $eligible->first()->id);
    }

    public function test_draw_excludes_soft_deleted_coupons(): void
    {
        $site = Site::factory()->create();
        $sweepstake = Sweepstake::factory()->create(['site_id' => $site->id]);

        $redemption = CouponRedemption::create([
            'sweepstake_id' => $sweepstake->id,
            'user_id' => User::factory()->create()->id,
            'coupon_count' => 2,
            'coupon_start_number' => 1,
            'coupon_end_number' => 2,
            'is_voided' => false,
        ]);

        $valid = SweepstakeCoupon::create([
            'sweepstake_id' => $sweepstake->id,
            'redemption_id' => $redemption->id,
            'user_id' => $redemption->user_id,
            'coupon_number' => 1,
            'is_voided' => false,
        ]);

        $deleted = SweepstakeCoupon::create([
            'sweepstake_id' => $sweepstake->id,
            'redemption_id' => $redemption->id,
            'user_id' => $redemption->user_id,
            'coupon_number' => 2,
            'is_voided' => false,
        ]);
        $deleted->delete();

        $eligible = app(SweepstakeDrawService::class)->getEligibleCoupons($sweepstake);

        $this->assertCount(1, $eligible);
        $this->assertSame($valid->id, $eligible->first()->id);
    }

    public function test_draw_excludes_voided_and_soft_deleted_coupons(): void
    {
        $site = Site::factory()->create();
        $sweepstake = Sweepstake::factory()->create(['site_id' => $site->id]);

        // 3 coupons: 1 valid, 1 voided, 1 soft-deleted
        $redemption = CouponRedemption::create([
            'sweepstake_id' => $sweepstake->id,
            'user_id' => User::factory()->create()->id,
            'coupon_count' => 3,
            'coupon_start_number' => 1,
            'coupon_end_number' => 3,
            'is_voided' => false,
        ]);

        $valid = SweepstakeCoupon::create([
            'sweepstake_id' => $sweepstake->id,
            'redemption_id' => $redemption->id,
            'user_id' => $redemption->user_id,
            'coupon_number' => 1,
            'is_voided' => false,
            'is_used' => false,
        ]);

        SweepstakeCoupon::create([
            'sweepstake_id' => $sweepstake->id,
            'redemption_id' => $redemption->id,
            'user_id' => $redemption->user_id,
            'coupon_number' => 2,
            'is_voided' => true,
            'is_used' => false,
        ]);

        $deleted = SweepstakeCoupon::create([
            'sweepstake_id' => $sweepstake->id,
            'redemption_id' => $redemption->id,
            'user_id' => $redemption->user_id,
            'coupon_number' => 3,
            'is_voided' => false,
            'is_used' => false,
        ]);
        $deleted->delete(); // soft delete

        $eligible = app(SweepstakeDrawService::class)->getEligibleCoupons($sweepstake);

        $this->assertCount(1, $eligible);
        $this->assertSame($valid->id, $eligible->first()->id);
    }

    public function test_draw_without_drawn_by_user_keeps_null_audit(): void
    {
        [$sweepstake] = $this->createSweepstakeWithCoupons(3);

        $draw = app(SweepstakeDrawService::class)->draw($sweepstake, 1);

        $this->assertNull($draw->drawn_by);
        $this->assertNull($draw->fresh()->drawnBy);
        // El cupón ganador debe tener used_by en null
        $this->assertNull($draw->winners->first()->fresh()->used_by);
    }

    public function test_draw_stores_notes(): void
    {
        [$sweepstake] = $this->createSweepstakeWithCoupons(3);

        $draw = app(SweepstakeDrawService::class)->draw($sweepstake, 1, null, 'Sorteo transmitido en vivo');

        $this->assertSame('Sorteo transmitido en vivo', $draw->notes);
    }

    public function test_draw_with_null_notes_persists_null(): void
    {
        [$sweepstake] = $this->createSweepstakeWithCoupons(3);

        $draw = app(SweepstakeDrawService::class)->draw($sweepstake, 1);

        $this->assertNull($draw->notes);
    }

    public function test_draw_sets_drawn_at_to_now(): void
    {
        [$sweepstake] = $this->createSweepstakeWithCoupons(3);
        $before = now()->subSecond();

        $draw = app(SweepstakeDrawService::class)->draw($sweepstake, 1);

        $this->assertNotNull($draw->drawn_at);
        $this->assertTrue($draw->drawn_at->greaterThan($before));
    }

    // ==========================================
    // Sorteos múltiples (historial)
    // ==========================================

    public function test_multiple_draws_on_same_sweepstake_creates_history(): void
    {
        [$sweepstake] = $this->createSweepstakeWithCoupons(20);

        $service = app(SweepstakeDrawService::class);
        $draw1 = $service->draw($sweepstake, 2);
        $draw2 = $service->draw($sweepstake, 3);

        $this->assertCount(2, $sweepstake->fresh()->draws);
        $this->assertSame(2, $draw1->winners_count);
        $this->assertSame(3, $draw2->winners_count);
        $this->assertNotSame($draw1->id, $draw2->id);
    }

    public function test_multiple_draws_do_not_overlap_winners(): void
    {
        // Aunque el universo es "todos los válidos", cada sorteo tiene su propio pivot
        [$sweepstake] = $this->createSweepstakeWithCoupons(10);

        $service = app(SweepstakeDrawService::class);
        $draw1 = $service->draw($sweepstake, 1);
        $draw2 = $service->draw($sweepstake, 1);

        $winner1 = $draw1->winners->first()->id;
        $winner2 = $draw2->winners->first()->id;

        // Cada sorteo tiene su propio ganador registrado en su pivot
        $this->assertDatabaseHas('sweepstake_draw_coupon', [
            'sweepstake_draw_id' => $draw1->id,
            'sweepstake_coupon_id' => $winner1,
            'position' => 1,
        ]);
        $this->assertDatabaseHas('sweepstake_draw_coupon', [
            'sweepstake_draw_id' => $draw2->id,
            'sweepstake_coupon_id' => $winner2,
            'position' => 1,
        ]);
    }

    // ==========================================
    // Service: SweepstakeDrawService::dispatchNotifications()
    // ==========================================

    public function test_dispatch_notifications_dispatches_job_and_flags_draw(): void
    {
        Queue::fake();

        [$sweepstake] = $this->createSweepstakeWithCoupons(5);
        $service = app(SweepstakeDrawService::class);

        $draw = $service->draw($sweepstake, 2);
        $ok = $service->dispatchNotifications($draw);

        $this->assertTrue($ok);
        Queue::assertPushed(NotifySweepstakeWinnersJob::class);
        $this->assertTrue($draw->fresh()->notified);
    }

    public function test_dispatch_notifications_skips_when_already_notified(): void
    {
        Queue::fake();

        [$sweepstake] = $this->createSweepstakeWithCoupons(5);
        $service = app(SweepstakeDrawService::class);

        $draw = $service->draw($sweepstake, 1);
        $service->dispatchNotifications($draw);

        // Segunda llamada debe ser ignorada (force=false)
        $ok = $service->dispatchNotifications($draw);

        $this->assertFalse($ok);
        Queue::assertPushed(NotifySweepstakeWinnersJob::class, 1);
    }

    public function test_dispatch_notifications_force_flag_redispatches_job(): void
    {
        Queue::fake();

        [$sweepstake] = $this->createSweepstakeWithCoupons(5);
        $service = app(SweepstakeDrawService::class);

        $draw = $service->draw($sweepstake, 1);
        $service->dispatchNotifications($draw);

        // Con force=true debe despacharse nuevamente
        $ok = $service->dispatchNotifications($draw, force: true);

        $this->assertTrue($ok);
        Queue::assertPushed(NotifySweepstakeWinnersJob::class, 2);
    }

    // ==========================================
    // Job: NotifySweepstakeWinnersJob
    // ==========================================

    public function test_notify_winners_job_sends_email_to_each_winner_with_user(): void
    {
        // Crear manualmente la plantilla FinMail para que TemplateMail::make('prize-won') funcione
        EmailTemplate::create([
            'key' => 'prize-won',
            'name' => ['es' => 'Ganador de Sorteo'],
            'category' => 'transactional',
            'subject' => ['es' => '¡Felicidades, ganaste!'],
            'preheader' => ['es' => 'Tu cupón resultó ganador.'],
            'body' => ['es' => '<h2>¡Hola {{ name }}!</h2><p>Ganaste con el cupón {{ coupon_number }}.</p>'],
            'from' => [],
            'reply_to' => [],
            'token_schema' => [
                'name' => 'string',
                'sweepstake_name' => 'string',
                'prize' => 'string',
                'coupon_number' => 'string',
                'position' => 'int',
            ],
            'is_active' => true,
        ]);

        Mail::fake();

        [$sweepstake] = $this->createSweepstakeWithCoupons(3);

        $draw = app(SweepstakeDrawService::class)->draw($sweepstake, 2);
        $draw->load(['winners.user', 'sweepstake']);

        // Ejecuta el job directamente (sin pasar por la cola)
        (new NotifySweepstakeWinnersJob($draw))->handle(app(SmsService::class));

        // TemplateMail implementa ShouldQueue, así que Mail::fake() lo encola
        Mail::assertQueued(TemplateMail::class, 2);
    }

    public function test_notify_winners_job_skips_winners_without_user(): void
    {
        Mail::fake();

        $site = Site::factory()->create();
        $sweepstake = Sweepstake::factory()->create(['site_id' => $site->id]);

        $redemption = CouponRedemption::create([
            'sweepstake_id' => $sweepstake->id,
            'user_id' => null,
            'coupon_count' => 2,
            'coupon_start_number' => 1,
            'coupon_end_number' => 2,
            'is_voided' => false,
        ]);

        SweepstakeCoupon::create([
            'sweepstake_id' => $sweepstake->id,
            'redemption_id' => $redemption->id,
            'user_id' => null,
            'coupon_number' => 1,
        ]);

        SweepstakeCoupon::create([
            'sweepstake_id' => $sweepstake->id,
            'redemption_id' => $redemption->id,
            'user_id' => null,
            'coupon_number' => 2,
        ]);

        $draw = app(SweepstakeDrawService::class)->draw($sweepstake, 2);
        $draw->load(['winners.user', 'sweepstake']);

        (new NotifySweepstakeWinnersJob($draw))->handle(app(SmsService::class));

        // Sin usuarios, no se envían correos
        Mail::assertNothingSent();
    }

    public function test_notify_winners_job_handles_missing_sweepstake_gracefully(): void
    {
        Mail::fake();

        [$sweepstake] = $this->createSweepstakeWithCoupons(3);
        $draw = app(SweepstakeDrawService::class)->draw($sweepstake, 1);
        $draw->load(['winners.user', 'sweepstake']);

        // Simular que la relación sweepstake devuelve null (sweepstake eliminada)
        // Forzamos la relación cacheada a null para testear el guard del job
        $draw->setRelation('sweepstake', null);

        // No debe lanzar excepción, simplemente salir sin enviar nada
        (new NotifySweepstakeWinnersJob($draw))->handle(app(SmsService::class));

        Mail::assertNothingSent();
    }

    // ==========================================
    // Modelos y relaciones
    // ==========================================

    public function test_winners_relation_is_ordered_by_position(): void
    {
        [$sweepstake] = $this->createSweepstakeWithCoupons(10);

        $draw = app(SweepstakeDrawService::class)->draw($sweepstake, 5);

        $positions = $draw->winners->pluck('pivot.position')->all();

        $this->assertSame([1, 2, 3, 4, 5], $positions);
    }

    public function test_sweepstake_draws_relation(): void
    {
        [$sweepstake] = $this->createSweepstakeWithCoupons(10);

        app(SweepstakeDrawService::class)->draw($sweepstake, 1);
        app(SweepstakeDrawService::class)->draw($sweepstake, 2);

        $this->assertCount(2, $sweepstake->fresh()->draws);
    }

    public function test_sweepstake_get_eligible_coupons_for_draw_loads_user_relation(): void
    {
        [$sweepstake] = $this->createSweepstakeWithCoupons(3);

        $eligible = $sweepstake->getEligibleCouponsForDraw();

        $this->assertCount(3, $eligible);
        $eligible->each(function (SweepstakeCoupon $coupon) {
            $this->assertTrue($coupon->relationLoaded('user'));
        });
    }

    public function test_sweepstake_coupon_draws_relation(): void
    {
        [$sweepstake, $coupons] = $this->createSweepstakeWithCoupons(5);

        $draw = app(SweepstakeDrawService::class)->draw($sweepstake, 1);
        $winnerCoupon = $draw->winners->first();

        // La relación inversa desde el cupón ganador
        $this->assertCount(1, $winnerCoupon->fresh()->draws);
        $this->assertSame($draw->id, $winnerCoupon->fresh()->draws->first()->id);
    }

    public function test_sweepstake_draw_casts(): void
    {
        [$sweepstake] = $this->createSweepstakeWithCoupons(3);

        $draw = app(SweepstakeDrawService::class)->draw($sweepstake, 1);

        $this->assertIsInt($draw->winners_count);
        $this->assertIsBool($draw->notified);
        $this->assertInstanceOf(Carbon::class, $draw->drawn_at);
    }

    public function test_sweepstake_draw_factory_creates_valid_model(): void
    {
        $draw = SweepstakeDraw::factory()->create();

        $this->assertNotNull($draw->sweepstake_id);
        $this->assertGreaterThanOrEqual(1, $draw->winners_count);
        $this->assertNotNull($draw->drawn_at);
        $this->assertDatabaseHas('sweepstake_draws', ['id' => $draw->id]);
    }

    public function test_pivot_table_enforces_unique_position_per_draw(): void
    {
        $this->expectException(QueryException::class);

        [$sweepstake] = $this->createSweepstakeWithCoupons(5);
        $draw = app(SweepstakeDrawService::class)->draw($sweepstake, 2);

        // Intentar insertar manualmente un duplicado de posición
        $winner = $draw->winners->first();
        \DB::table('sweepstake_draw_coupon')->insert([
            'sweepstake_draw_id' => $draw->id,
            'sweepstake_coupon_id' => $winner->id,
            'user_id' => $winner->user_id,
            'position' => 1, // duplicado
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_pivot_table_enforces_unique_coupon_per_draw(): void
    {
        $this->expectException(QueryException::class);

        [$sweepstake] = $this->createSweepstakeWithCoupons(5);
        $draw = app(SweepstakeDrawService::class)->draw($sweepstake, 2);

        $winner = $draw->winners->first();
        \DB::table('sweepstake_draw_coupon')->insert([
            'sweepstake_draw_id' => $draw->id,
            'sweepstake_coupon_id' => $winner->id, // mismo cupón
            'user_id' => $winner->user_id,
            'position' => 999, // posición nueva, pero cupón duplicado
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_deleting_sweepstake_cascades_draws(): void
    {
        [$sweepstake] = $this->createSweepstakeWithCoupons(5);
        $draw = app(SweepstakeDrawService::class)->draw($sweepstake, 1);
        $drawId = $draw->id;

        // SoftDeletes: usar forceDelete para que la FK cascade elimine el draw
        $sweepstake->forceDelete();

        // La FK on-delete cascade debe eliminar el draw
        $this->assertDatabaseMissing('sweepstake_draws', ['id' => $drawId]);
    }

    public function test_drawn_by_relation_returns_user(): void
    {
        [$sweepstake] = $this->createSweepstakeWithCoupons(3);
        $drawnBy = User::factory()->create();

        $draw = app(SweepstakeDrawService::class)->draw($sweepstake, 1, $drawnBy);

        $this->assertInstanceOf(User::class, $draw->drawnBy);
        $this->assertSame($drawnBy->id, $draw->drawnBy->id);
    }
}
