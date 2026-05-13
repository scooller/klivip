<?php

namespace App\Filament\Resources\Coupons\Schemas;

use App\Enums\CouponType;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CouponInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('code'),
                TextEntry::make('site.name')
                    ->label('Sitio'),
                TextEntry::make('type')
                    ->formatStateUsing(function ($state): string {
                        if ($state instanceof CouponType) {
                            return $state->label();
                        }

                        return ucfirst((string) $state);
                    }),
                TextEntry::make('value'),
                TextEntry::make('used_count')
                    ->label('Usos'),
                TextEntry::make('max_uses')
                    ->label('Límite de usos'),
                TextEntry::make('valid_from')
                    ->dateTime(),
                TextEntry::make('valid_to')
                    ->dateTime(),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
