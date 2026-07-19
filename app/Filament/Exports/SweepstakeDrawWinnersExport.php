<?php

namespace App\Filament\Exports;

use App\Models\SweepstakeCoupon;
use App\Models\SweepstakeDraw;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class SweepstakeDrawWinnersExport implements FromCollection, WithCustomCsvSettings, WithHeadings, WithMapping, WithTitle
{
    use Exportable;

    protected int $drawId;

    public function __construct(int $drawId)
    {
        $this->drawId = $drawId;
    }

    public static function forDraw(int $drawId): self
    {
        return new self($drawId);
    }

    public function collection(): Collection
    {
        $draw = SweepstakeDraw::with([
            'winners.user',
            'winners.sweepstake.site',
            'winners.redemption',
            'drawnBy',
            'sweepstake',
        ])->findOrFail($this->drawId);

        return $draw->winners->map(fn(SweepstakeCoupon $coupon) => [
            'coupon' => $coupon,
            'draw' => $draw,
        ]);
    }

    public function headings(): array
    {
        return [
            'posicion',
            'sorteo_id',
            'sorteo_nombre',
            'sorteo_slug',
            'site_id',
            'site_nombre',
            'sorteo_fecha',
            'sorteo_realizado_por',
            'cupon_id',
            'cupon_numero',
            'cupon_display',
            'usuario_id',
            'usuario_nombre',
            'usuario_email',
            'usuario_telefono',
            'notificado',
            'observaciones',
        ];
    }

    /**
     * @param  array{coupon: SweepstakeCoupon, draw: SweepstakeDraw}  $row
     */
    public function map($row): array
    {
        $coupon = $row['coupon'];
        $draw = $row['draw'];
        $sweepstake = $coupon->sweepstake ?? $draw->sweepstake;
        $site = $sweepstake->site ?? null;
        $user = $coupon->user;

        return [
            (int) ($coupon->pivot->position ?? 0),
            $sweepstake->id,
            $sweepstake->name,
            $sweepstake->slug,
            $site?->id,
            $site?->name,
            $draw->drawn_at?->format('Y-m-d H:i:s'),
            $draw->drawnBy?->name,
            $coupon->id,
            $coupon->coupon_number,
            $coupon->getDisplayNumber(),
            $coupon->user_id,
            $user?->name,
            $user?->email,
            $user?->phone,
            $draw->notified ? 'Sí' : 'No',
            $draw->notes,
        ];
    }

    public function getCsvSettings(): array
    {
        return [
            'input_encoding' => 'UTF-8',
            'output_encoding' => 'UTF-8',
        ];
    }

    public function title(): string
    {
        $draw = SweepstakeDraw::with('sweepstake')->find($this->drawId);
        $name = $draw?->sweepstake?->name ?? 'Desconocido';
        $fecha = $draw?->drawn_at?->format('d-m-Y H:i') ?? 's/f';

        return sprintf('Ganadores del sorteo: %s (%s)', $name, $fecha);
    }

    public function fileName(): string
    {
        $draw = SweepstakeDraw::with('sweepstake')->find($this->drawId);
        $slug = $draw?->sweepstake?->slug ?? 'desconocido';
        $fecha = $draw?->drawn_at?->format('Y-m-d') ?? now()->format('Y-m-d');

        return sprintf('sorteo-%s-ganadores-%s.csv', $slug, $fecha);
    }
}
