<?php

namespace App\Enums;

enum PromotionScheduleType: string
{
    case Standard = 'standard';
    case Recurrent = 'recurrent';
    case Special = 'special';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Standard->value => 'Normal',
            self::Recurrent->value => 'Recurrente',
            self::Special->value => 'Especial',
        ];
    }

    public function label(): string
    {
        return self::options()[$this->value];
    }
}
