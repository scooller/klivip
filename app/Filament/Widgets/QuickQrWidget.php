<?php

namespace App\Filament\Widgets;

use App\Models\QrBonus;
use App\Models\RedemptionLink;
use App\Models\RedemptionSource;
use App\Models\Site;
use App\Models\Sweepstake;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Widgets\Widget;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QuickQrWidget extends Widget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected string $view = 'filament.widgets.quick-qr-widget';

    public ?int $lastCreatedLinkId = null;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('qr_bonus_id')
                    ->label('Usar Bono QR (Plantilla)')
                    ->options(QrBonus::pluck('name', 'id'))
                    ->live()
                    ->afterStateUpdated(function (Set $set, $state) {
                        if ($state) {
                            $bonus = QrBonus::find($state);
                            if ($bonus) {
                                $set('coupon_count', $bonus->coupon_count);
                                $set('max_redemptions', $bonus->max_redemptions);
                            }
                        }
                    })
                    ->placeholder('Seleccionar un bono (opcional)'),
                TextInput::make('batch_name')
                    ->label('Nombre del QR')
                    ->required()
                    ->default(fn () => Sweepstake::latest()->first()?->name ?? 'Nuevo QR')
                    ->placeholder('Ej: Rápido - Evento Julio')
                    ->maxLength(255),
                TextInput::make('coupon_count')
                    ->label('Cupones por QR')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->default(1),
                TextInput::make('max_redemptions')
                    ->label('Máximo de redenciones por QR')
                    ->numeric()
                    ->minValue(1)
                    ->default(1),
            ])
            ->statePath('data');
    }

    public function generateQr(): void
    {
        $data = $this->form->getState();

        $latestSweepstake = Sweepstake::latest()->first();

        if (! $latestSweepstake) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('No hay sorteos disponibles.')
                ->send();

            return;
        }

        $code = Str::random(12);
        $qrSource = RedemptionSource::where('code', 'qr')->first();

        $link = $latestSweepstake->redemptionLinks()->create([
            'redemption_source_id' => $qrSource ? $qrSource->id : null,
            'code' => $code,
            'title' => $data['batch_name'],
            'description' => 'Generado desde widget rápido',
            'coupon_count' => $data['coupon_count'],
            'max_redemptions' => $data['max_redemptions'],
            'is_active' => true,
        ]);

        $this->lastCreatedLinkId = $link->id;

        Notification::make()
            ->success()
            ->title('QR generado exitosamente')
            ->body("Se creó el QR para el sorteo {$latestSweepstake->name}")
            ->send();

        $this->form->fill();

        $this->mountAction('viewQrAction');
    }

    public function viewQrAction(): Action
    {
        return Action::make('viewQrAction')
            ->label('Ver QR')
            ->modalHeading(fn () => 'QR Generado')
            ->modalSubmitActionLabel('Descargar QR')
            ->modalCancelActionLabel('Cerrar')
            ->modalContent(function () {
                if (! $this->lastCreatedLinkId) {
                    return null;
                }
                $record = RedemptionLink::find($this->lastCreatedLinkId);
                if (! $record) {
                    return null;
                }

                $site = $record->sweepstake->site ?? Site::first();
                $url = str_replace(
                    '://',
                    "://{$site->slug}.",
                    config('app.url')
                )."/redimir/{$record->code}";

                $qrSvg = QrCode::format('svg')
                    ->size(300)
                    ->margin(2)
                    ->errorCorrection('H')
                    ->generate($url);

                return view('filament.actions.show-coupon-qr-code', [
                    'qrSvg' => $qrSvg,
                    'url' => $url,
                ]);
            })
            ->action(function () {
                if (! $this->lastCreatedLinkId) {
                    return;
                }
                $record = RedemptionLink::find($this->lastCreatedLinkId);
                if (! $record) {
                    return;
                }

                $site = $record->sweepstake->site ?? Site::first();
                $url = str_replace(
                    '://',
                    "://{$site->slug}.",
                    config('app.url')
                )."/redimir/{$record->code}";

                $qrCode = QrCode::format('png')
                    ->size(400)
                    ->margin(10)
                    ->errorCorrection('H')
                    ->generate($url);

                return response()->streamDownload(
                    function () use ($qrCode) {
                        echo $qrCode;
                    },
                    "qr-{$record->code}.png"
                );
            });
    }
}
