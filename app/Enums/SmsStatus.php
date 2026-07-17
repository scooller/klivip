<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum SmsStatus: int implements HasColor, HasIcon, HasLabel
{
    case Draft = 1;
    case Queued = 2;
    case Sent = 3;
    case Failed = 4;

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Queued => 'En cola',
            self::Sent => 'Enviado',
            self::Failed => 'Fallido',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Queued => 'warning',
            self::Sent => 'success',
            self::Failed => 'danger',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Draft => Heroicon::OutlinedPencilSquare,
            self::Queued => Heroicon::OutlinedClock,
            self::Sent => Heroicon::OutlinedCheckCircle,
            self::Failed => Heroicon::OutlinedXCircle,
        };
    }
}
