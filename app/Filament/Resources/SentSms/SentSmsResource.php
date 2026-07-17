<?php

namespace App\Filament\Resources\SentSms;

use App\Filament\Resources\SentSms\Pages\ListSentSms;
use App\Filament\Resources\SentSms\Tables\SentSmsTable;
use App\Models\SentSms;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class SentSmsResource extends Resource
{
    protected static ?string $model = SentSms::class;

    protected static ?string $navigationLabel = 'Mensajes SMS';

    protected static ?string $slug = 'sent-sms';

    public static function getNavigationGroup(): ?string
    {
        return 'Mensajería';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-device-phone-mobile';
    }

    public static function table(Table $table): Table
    {
        return SentSmsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSentSms::route('/'),
        ];
    }
}
