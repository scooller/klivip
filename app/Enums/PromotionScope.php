<?php

namespace App\Enums;

enum PromotionScope: string
{
    case Site = 'site';
    case Global = 'global';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Site->value => 'Por sitio',
            self::Global->value => 'Global',
        ];
    }

    public function label(): string
    {
        return self::options()[$this->value];
    }
}
