<?php

namespace App\Filament\Resources\SmsTemplates;

use App\Filament\Resources\SmsTemplates\Pages\CreateSmsTemplate;
use App\Filament\Resources\SmsTemplates\Pages\EditSmsTemplate;
use App\Filament\Resources\SmsTemplates\Pages\ListSmsTemplates;
use App\Filament\Resources\SmsTemplates\Pages\ViewSmsTemplate;
use App\Filament\Resources\SmsTemplates\Schemas\SmsTemplateForm;
use App\Filament\Resources\SmsTemplates\Tables\SmsTemplatesTable;
use App\Models\SmsTemplate;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class SmsTemplateResource extends Resource
{
    protected static ?string $model = SmsTemplate::class;

    protected static ?string $navigationLabel = 'Plantillas SMS';

    protected static ?string $slug = 'sms-templates';

    public static function getNavigationGroup(): ?string
    {
        return 'Mensajería';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-chat-bubble-left-ellipsis';
    }

    public static function form(Schema $schema): Schema
    {
        return SmsTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SmsTemplatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSmsTemplates::route('/'),
            'create' => CreateSmsTemplate::route('/create'),
            'view' => ViewSmsTemplate::route('/{record}'),
            'edit' => EditSmsTemplate::route('/{record}/edit'),
        ];
    }
}
