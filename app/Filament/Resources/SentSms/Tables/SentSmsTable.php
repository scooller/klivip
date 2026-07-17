<?php

namespace App\Filament\Resources\SentSms\Tables;

use App\Enums\SmsStatus;
use App\Filament\Resources\SentSms\Schemas\SentSmsInfolist;
use App\Models\SentSms;
use App\Services\SmsService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SentSmsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('to')
                    ->label('Destinatario')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('template.name.es')
                    ->label('Plantilla')
                    ->badge()
                    ->color('gray')
                    ->placeholder('Directo')
                    ->toggleable(),

                TextColumn::make('from')
                    ->label('Remitente')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('body')
                    ->label('Mensaje')
                    ->limit(60)
                    ->wrap()
                    ->tooltip(fn(SentSms $record): ?string => mb_strlen($record->body) > 60 ? $record->body : null),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->sortable(),

                TextColumn::make('error_message')
                    ->label('Error')
                    ->limit(40)
                    ->color('danger')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn($record): bool => $record?->status === SmsStatus::Failed),

                TextColumn::make('sent_at')
                    ->label('Enviado')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('sendable_type')
                    ->label('Origen')
                    ->formatStateUsing(fn(?string $state): string => $state ? class_basename($state) : '—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(SmsStatus::class),

                SelectFilter::make('sms_template_id')
                    ->label('Plantilla')
                    ->relationship('template', 'key'),

                Filter::make('sent_at')
                    ->label('Fecha de envío')
                    ->schema([
                        DatePicker::make('from')->label('Desde'),
                        DatePicker::make('until')->label('Hasta'),
                    ])
                    ->query(
                        fn($query, array $data) => $query
                            ->when($data['from'], fn($q, $date) => $q->whereDate('sent_at', '>=', $date))
                            ->when($data['until'], fn($q, $date) => $q->whereDate('sent_at', '<=', $date))
                    ),

                Filter::make('only_errors')
                    ->label('Solo errores')
                    ->query(fn($query) => $query->where('status', SmsStatus::Failed)),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Ver')
                    ->icon(Heroicon::OutlinedEye)
                    ->modal()
                    ->modalHeading(fn(SentSms $record): string => "SMS → {$record->to}")
                    ->schema(SentSmsInfolist::schema())
                    ->modalWidth(Width::FourExtraLarge)
                    ->modalSubmitAction(false)
                    ->extraModalFooterActions([
                        Action::make('resend')
                            ->label('Reenviar')
                            ->icon(Heroicon::OutlinedArrowPath)
                            ->color('warning')
                            ->requiresConfirmation()
                            ->action(function (SentSms $record, SmsService $smsService): void {
                                try {
                                    $smsService->resend($record);

                                    Notification::make()
                                        ->title('SMS reenviado correctamente')
                                        ->success()
                                        ->send();
                                } catch (\Throwable $e) {
                                    Notification::make()
                                        ->title('Error al reenviar SMS')
                                        ->body($e->getMessage())
                                        ->danger()
                                        ->send();
                                }
                            }),
                    ]),
            ])
            ->poll('30s');
    }
}
