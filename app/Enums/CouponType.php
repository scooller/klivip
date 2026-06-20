<?php

namespace App\Enums;

enum CouponType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';
    case Message = 'mensaje';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Percentage->value => 'Percentage',
            self::Fixed->value => 'Fixed Amount',
            self::Message->value => 'Mensaje',
        ];
    }

    public function label(): string
    {
        return self::options()[$this->value];
    }
}
