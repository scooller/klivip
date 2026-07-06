<?php

namespace App\Filament\Exports;

use App\Models\CouponRedemption;
use Illuminate\Database\Eloquent\Builder;

class CouponRedemptionsExport extends CsvExporter
{
    protected ?int $sweepstakeId = null;

    protected ?int $userId = null;

    public static function forSweepstake(int $sweepstakeId): self
    {
        $instance = new self;
        $instance->sweepstakeId = $sweepstakeId;

        return $instance;
    }

    public static function forUser(int $userId): self
    {
        $instance = new self;
        $instance->userId = $userId;

        return $instance;
    }

    /**
     * @return array<string, string>
     */
    public static function columns(): array
    {
        return [
            'id' => 'ID',
            'sweepstake' => 'Sorteo',
            'redemption_link' => 'Origen/Link',
            'source_type' => 'Tipo',
            'user_name' => 'Nombre',
            'user_email' => 'Email',
            'user_phone' => 'Teléfono',
            'coupon_count' => 'Cupones',
            'coupon_start_number' => 'N° inicial',
            'coupon_end_number' => 'N° final',
            'redemption_channel' => 'Canal',
            'ip_address' => 'IP',
            'is_voided' => 'Anulado',
            'voided_reason' => 'Motivo anulación',
            'voided_at' => 'Fecha anulación',
            'created_at' => 'Fecha redención',
        ];
    }

    protected function query(): Builder
    {
        $query = CouponRedemption::query()
            ->with(['sweepstake', 'redemptionLink.redemptionSource']);

        if ($this->sweepstakeId !== null) {
            $query->where('sweepstake_id', $this->sweepstakeId);
        }

        if ($this->userId !== null) {
            $query->where('user_id', $this->userId);
        }

        return $query->orderByDesc('created_at');
    }

    /**
     * @param  array<string>  $selected
     * @return array<string, mixed>
     */
    protected function mapRow(object $record, array $selected): array
    {
        /** @var CouponRedemption $record */
        $link = $record->redemptionLink;

        return [
            'id' => $record->id,
            'sweepstake' => $record->sweepstake?->name,
            'redemption_link' => $link?->title,
            'source_type' => $link?->redemptionSource?->name,
            'user_name' => $record->user_name,
            'user_email' => $record->user_email,
            'user_phone' => $record->user_phone,
            'coupon_count' => $record->coupon_count,
            'coupon_start_number' => $record->coupon_start_number,
            'coupon_end_number' => $record->coupon_end_number,
            'redemption_channel' => $record->redemption_channel,
            'ip_address' => $record->ip_address,
            'is_voided' => $record->is_voided ? 'Sí' : 'No',
            'voided_reason' => $record->voided_reason,
            'voided_at' => $record->voided_at,
            'created_at' => $record->created_at,
        ];
    }
}
