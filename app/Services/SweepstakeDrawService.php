<?php

namespace App\Services;

use App\Jobs\NotifySweepstakeWinnersJob;
use App\Models\Sweepstake;
use App\Models\SweepstakeCoupon;
use App\Models\SweepstakeDraw;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SweepstakeDrawService
{
    /**
     * Obtiene los cupones válidos del sorteo, cargando la relación user.
     *
     * @return Collection<int, SweepstakeCoupon>
     */
    public function getEligibleCoupons(Sweepstake $sweepstake): Collection
    {
        return $sweepstake->getEligibleCouponsForDraw();
    }

    /**
     * Ejecuta un sorteo aleatorio sobre los cupones válidos del sorteo.
     *
     * @param  ?User  $drawnBy  Operador que ejecuta el sorteo (auditoría).
     *
     * @throws RuntimeException Si no hay cupones suficientes para el sorteo.
     */
    public function draw(Sweepstake $sweepstake, int $winnersCount, ?User $drawnBy = null, ?string $notes = null): SweepstakeDraw
    {
        if ($winnersCount < 1) {
            throw new RuntimeException('La cantidad de ganadores debe ser mayor o igual a 1.');
        }

        $eligible = $this->getEligibleCoupons($sweepstake);

        if ($eligible->count() < $winnersCount) {
            throw new RuntimeException(sprintf(
                'No hay cupones suficientes para sortear %d ganador(es). Cupones válidos disponibles: %d.',
                $winnersCount,
                $eligible->count()
            ));
        }

        // Selección criptográficamente segura sin repetición.
        $winnerCoupons = $eligible
            ->random($winnersCount)
            ->values();

        return DB::transaction(function () use ($sweepstake, $winnerCoupons, $drawnBy, $notes): SweepstakeDraw {
            $draw = SweepstakeDraw::create([
                'sweepstake_id' => $sweepstake->id,
                'drawn_by' => $drawnBy?->id,
                'winners_count' => $winnerCoupons->count(),
                'notes' => $notes,
                'drawn_at' => now(),
                'notified' => false,
            ]);

            // Adjunta cada cupón ganador con su posición, marca is_used y dispara el job.
            $winnerCoupons->each(function (SweepstakeCoupon $coupon, int $index) use ($draw, $drawnBy): void {
                $draw->winners()->attach($coupon->id, [
                    'position' => $index + 1,
                    'user_id' => $coupon->user_id,
                ]);

                $coupon->markAsUsed($drawnBy);
            });

            return $draw->refresh()->load(['winners.user', 'drawnBy']);
        });
    }

    /**
     * Despacha las notificaciones Email + SMS a los ganadores del sorteo.
     * Devuelve true si el job fue encolado exitosamente.
     */
    public function dispatchNotifications(SweepstakeDraw $draw, bool $force = false): bool
    {
        if ($draw->notified && ! $force) {
            Log::info('SweepstakeDraw already notified, skipping dispatch', [
                'draw_id' => $draw->id,
            ]);

            return false;
        }

        try {
            NotifySweepstakeWinnersJob::dispatch($draw->fresh(['winners.user.sweepstakeCoupons.sweepstake', 'sweepstake']));
            $draw->update(['notified' => true]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to dispatch NotifySweepstakeWinnersJob', [
                'draw_id' => $draw->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
