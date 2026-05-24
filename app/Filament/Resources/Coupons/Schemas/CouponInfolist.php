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
                IconEntry::make('qr_enabled')
                    ->label('QR habilitado')
                    ->boolean(),
                TextEntry::make('qr_token')
                    ->label('Token QR'),
                TextEntry::make('qr_redeem_url')
                    ->label('URL de cobro QR')
                    ->state(fn ($record): string => $record->qr_redeem_url ?? '-'),
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
