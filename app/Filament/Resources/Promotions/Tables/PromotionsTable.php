<?php

namespace App\Filament\Resources\Promotions\Tables;

use App\Enums\PromotionScheduleType;
use App\Enums\PromotionScope;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PromotionsTable
{
    private static function formatRecurringDays(mixed $state): string
    {
        $normalizedState = match (true) {
            is_array($state) => $state,
            is_int($state) => [$state],
            is_string($state) && trim($state) !== '' => self::normalizeRecurringDaysString($state),
            default => [],
        };

        if ($normalizedState === []) {
            return '-';
        }

        $labels = [
            1 => 'Lun',
            2 => 'Mar',
            3 => 'Mié',
            4 => 'Jue',
            5 => 'Vie',
            6 => 'Sáb',
            7 => 'Dom',
        ];

        $days = array_map('intval', $normalizedState);
        sort($days);

        return collect($days)
            ->map(fn (int $day): string => $labels[$day] ?? (string) $day)
            ->implode(', ');
    }

    /**
     * @return array<int, int|string>
     */
    private static function normalizeRecurringDaysString(string $state): array
    {
        $normalized = \trim($state);

        if (\str_starts_with($normalized, '[') && \str_ends_with($normalized, ']')) {
            $normalized = \trim($normalized, '[]');
        }

        if (\str_contains($normalized, ',')) {
            return array_filter(
                array_map('trim', \explode(',', $normalized)),
                static fn (string $value): bool => $value !== '',
            );
        }

        return $normalized !== '' ? [$normalized] : [];
    }

    public static function configure(Table $table): Table
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('offer_label')
                    ->label('Oferta')
                    ->searchable(),
                TextColumn::make('scope')
                    ->label('Alcance')
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        if ($state instanceof PromotionScope) {
                            return $state->label();
                        }

                        return PromotionScope::options()[(string) $state] ?? (string) $state;
                    })
                    ->sortable(),
                TextColumn::make('site.name')
                    ->label('Sitio')
                    ->formatStateUsing(fn ($state, $record): string => $record->isGlobal() ? 'Global' : ((string) ($state ?? '-')))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('schedule_type')
                    ->label('Calendario')
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        if ($state instanceof PromotionScheduleType) {
                            return $state->label();
                        }

                        return PromotionScheduleType::options()[(string) $state] ?? (string) $state;
                    })
                    ->sortable(),
                TextColumn::make('recurrent_days')
                    ->label('Recurrencia')
                    ->formatStateUsing(fn ($state): string => self::formatRecurringDays($state)),
                TextColumn::make('special_date')
                    ->label('Fecha especial')
                    ->date(),
                TextColumn::make('starts_at')
                    ->label('Desde')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Hasta')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('scope')
                    ->label('Alcance')
                    ->options(PromotionScope::options()),
                SelectFilter::make('schedule_type')
                    ->label('Calendario')
                    ->options(PromotionScheduleType::options()),
                SelectFilter::make('site_id')
                    ->label('Sitio')
                    ->relationship(
                        'site',
                        'name',
                        modifyQueryUsing: function ($query) use ($user) {
                            if ($user instanceof User && ! $user->isSuperAdmin()) {
                                $query->whereIn('id', $user->sites()->select('sites.id'));
                            }
                        },
                    ),
                TernaryFilter::make('is_active')
                    ->label('Activo'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
