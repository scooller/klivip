<?php

namespace App\Enums;

enum BannerScope: string
{
    case Sites = 'sites';
    case Global = 'global';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Sites->value => 'Por sitios',
            self::Global->value => 'Global',
        ];
    }

    public function label(): string
    {
        return self::options()[$this->value];
    }
}
