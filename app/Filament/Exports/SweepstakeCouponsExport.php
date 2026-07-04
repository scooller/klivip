<?php

namespace App\Filament\Exports;

use App\Models\Sweepstake;
use App\Models\SweepstakeCoupon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class SweepstakeCouponsExport implements FromQuery, WithChunkReading, WithCustomCsvSettings, WithHeadings, WithMapping, WithTitle
{
    use Exportable;

    protected int $sweepstakeId;

    public function __construct(int $sweepstakeId)
    {
        $this->sweepstakeId = $sweepstakeId;
    }

    public static function forSweepstake(int $sweepstakeId): self
    {
        return new self($sweepstakeId);
    }

    public function query()
    {
        return SweepstakeCoupon::query()
            ->where('sweepstake_id', $this->sweepstakeId)
            ->where('is_voided', false)
            ->whereNull('deleted_at')
            ->with([
                'sweepstake.site',
                'redemption.redemptionLink.redemptionSource',
                'user',
            ])
            ->orderBy('coupon_number');
    }

    public function headings(): array
    {
        return [
            'sorteo_id',
            'sorteo_nombre',
            'sorteo_slug',
            'site_id',
            'site_nombre',
            'cupon_numero',
            'cupon_display',
            'usuario_id',
            'usuario_email',
            'usuario_telefono',
            'usuario_nombre',
            'fecha_cobro',
            'origen_id',
            'origen_titulo',
            'origen_tipo',
            'origen_descripcion',
            'redencion_id',
            'estado_anulado',
            'fecha_anulacion',
            'motivo_anulacion',
        ];
    }

    public function map($coupon): array
    {
        $redemption = $coupon->redemption;
        $link = $redemption->redemptionLink;
        $source = $link->redemptionSource;
        $sweepstake = $coupon->sweepstake;
        $site = $sweepstake->site;

        return [
            $sweepstake->id,
            $sweepstake->name,
            $sweepstake->slug,
            $site->id,
            $site->name,
            $coupon->coupon_number,
            $coupon->getDisplayNumber(),
            $coupon->user_id,
            $redemption->user_email,
            $redemption->user_phone,
            $redemption->user_name,
            $redemption->created_at->format('Y-m-d H:i:s'),
            $link->id,
            $link->title,
            $source->name,
            $link->description,
            $redemption->id,
            $coupon->is_voided ? 'Sí' : 'No',
            $coupon->voided_at?->format('Y-m-d H:i:s'),
            $coupon->voided_reason,
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
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
        $sweepstake = Sweepstake::find($this->sweepstakeId);

        return sprintf('Cupones del Sorteo: %s', $sweepstake->name ?? 'Desconocido');
    }

    public function fileName(): string
    {
        $sweepstake = Sweepstake::find($this->sweepstakeId);

        return sprintf(
            'cupones-sorteo-%s-%s.csv',
            $sweepstake->slug ?? 'desconocido',
            now()->format('Y-m-d')
        );
    }
}
