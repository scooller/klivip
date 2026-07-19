<?php

namespace App\Filament\Resources\Sweepstakes\Actions;

use App\Models\Sweepstake;
use App\Services\SweepstakeDrawService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\View;
use Illuminate\Database\Eloquent\Model;

class DrawSweepstakeAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'draw';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Sortear')
            ->icon('heroicon-o-sparkles')
            ->color('warning')
            ->modalHeading('Sortear ganadores')
            ->modalDescription('Selecciona al azar los cupones ganadores entre los cupones válidos cobrados.')
            ->modalSubmitActionLabel('Realizar sorteo')
            ->modalWidth('5xl')
            ->visible(fn (Model $record): bool => $record instanceof Sweepstake && $record->validCoupons()->exists())
            ->fillForm(fn (): array => [
                'winners_count' => 1,
                'notify_winners' => true,
            ])
            ->schema([
                View::make('filament.actions.draw-sweepstake')
                    ->viewData(fn (Model $record): array => [
                        'sweepstake' => $record,
                    ]),
                TextInput::make('winners_count')
                    ->label('Cantidad de ganadores')
                    ->numeric()
                    ->minValue(1)
                    ->required()
                    ->helperText(fn (Model $record): string => 'Máximo: '.$record->validCoupons()->count().' cupones válidos'),
                Toggle::make('notify_winners')
                    ->label('Notificar a los ganadores por email y SMS')
                    ->default(true)
                    ->helperText('Se despachará un job en cola para no bloquear la operación.'),
                Textarea::make('notes')
                    ->label('Observaciones')
                    ->rows(2)
                    ->placeholder('Opcional — ej: sorteo público, transmisión, etc.'),
            ])
            ->action(function (array $data, Model $record, Action $action): void {
                /** @var Sweepstake $record */
                $service = app(SweepstakeDrawService::class);

                try {
                    $draw = $service->draw(
                        sweepstake: $record,
                        winnersCount: (int) $data['winners_count'],
                        drawnBy: auth()->user(),
                        notes: $data['notes'] ?? null,
                    );

                    if (($data['notify_winners'] ?? true)) {
                        $service->dispatchNotifications($draw);
                    }

                    $winnersList = $draw->winners
                        ->map(fn ($coupon) => sprintf(
                            '#%d — %s (posición %d)',
                            $coupon->coupon_number,
                            $coupon->user?->name ?? 'Sin usuario',
                            (int) $coupon->pivot->position,
                        ))
                        ->implode("\n");

                    Notification::make()
                        ->title('Sorteo realizado')
                        ->body("Se seleccionaron {$draw->winners_count} ganador(es):\n\n{$winnersList}")
                        ->success()
                        ->persistent()
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('No se pudo realizar el sorteo')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();

                    $action->halt();
                }
            });
    }
}
