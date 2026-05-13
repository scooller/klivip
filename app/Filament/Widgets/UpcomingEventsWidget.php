<?php

namespace App\Filament\Widgets;

use App\Enums\PromotionScheduleType;
use App\Models\Promotion;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class UpcomingEventsWidget extends BaseWidget implements HasTable
{
    use InteractsWithTable;

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        $user = Auth::user();

        $query = Promotion::query()
            ->where('is_active', true)
            ->where('schedule_type', '!=', 'standard');

        if ($user instanceof User && ! $user->isSuperAdmin()) {
            $query->where(function (Builder $builder) use ($user): void {
                $builder->where('scope', 'global')
                    ->orWhereIn('site_id', $user->sites()->select('sites.id'));
            });
        }

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('title')
                    ->label('Evento')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('site.name')
                    ->label('Sitio')
                    ->placeholder('Global')
                    ->sortable(),

                TextColumn::make('schedule_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(function (PromotionScheduleType|string|null $state): string {
                        if ($state instanceof PromotionScheduleType) {
                            return $state->label();
                        }

                        if (is_string($state)) {
                            return match ($state) {
                                'standard' => 'Normal',
                                'recurrent' => 'Recurrente',
                                'special' => 'Especial',
                                default => $state,
                            };
                        }

                        return '-';
                    })
                    ->color(function (PromotionScheduleType|string|null $state): string {
                        $value = $state instanceof PromotionScheduleType
                            ? $state->value
                            : $state;

                        return match ($value) {
                            'recurrent' => 'info',
                            'special' => 'warning',
                            default => 'gray',
                        };
                    }),

                TextColumn::make('special_date')
                    ->label('Fecha')
                    ->date()
                    ->placeholder('Recurrente'),

                TextColumn::make('ends_at')
                    ->label('Vence')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10]);
    }
}
