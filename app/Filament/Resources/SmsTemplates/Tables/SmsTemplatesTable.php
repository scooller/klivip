<?php

namespace App\Filament\Resources\SmsTemplates\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SmsTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('key')
                    ->label('Clave')
                    ->searchable()
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('name.es')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category')
                    ->label('Categoría')
                    ->badge()
                    ->sortable(),

                TextColumn::make('sender_name')
                    ->label('Remitente')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('body.es')
                    ->label('Vista previa')
                    ->limit(60)
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('sent_sms_count')
                    ->label('Envíos')
                    ->counts('sentSms')
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Actualizada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Categoría')
                    ->options([
                        'transactional' => 'Transaccional',
                        'marketing' => 'Marketing',
                        'auth' => 'Autenticación',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('Activa'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
