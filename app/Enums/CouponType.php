<?php

namespace App\Enums;

enum CouponType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Percentage->value => 'Percentage',
            self::Fixed->value => 'Fixed Amount',
        ];
    }

    public function label(): string
    {
        return self::options()[$this->value];
    }
}
