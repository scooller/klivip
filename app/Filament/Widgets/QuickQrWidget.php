<?php

namespace App\Filament\Widgets;

use App\Models\QrBonus;
use App\Models\RedemptionSource;
use App\Models\Sweepstake;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Str;

class QuickQrWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = "filament.widgets.quick-qr-widget";

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = "full";

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make("qr_bonus_id")
                    ->label("Usar Bono QR (Plantilla)")
                    ->options(QrBonus::pluck("name", "id"))
                    ->live()
                    ->afterStateUpdated(function (Set $set, $state) {
                        if ($state) {
                            $bonus = QrBonus::find($state);
                            if ($bonus) {
                                $set("coupon_count", $bonus->coupon_count);
                                $set("max_redemptions", $bonus->max_redemptions);
                            }
                        }
                    })
                    ->placeholder("Seleccionar un bono (opcional)"),
                TextInput::make("batch_name")
                    ->label("Nombre del QR")
                    ->required()
                    ->placeholder("Ej: Rápido - Evento Julio")
                    ->maxLength(255),
                TextInput::make("coupon_count")
                    ->label("Cupones por QR")
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->default(1),
                TextInput::make("max_redemptions")
                    ->label("Máximo de redenciones por QR")
                    ->numeric()
                    ->minValue(1)
                    ->default(1),
            ])
            ->statePath("data");
    }

    public function generateQr(): void
    {
        $data = $this->form->getState();

        $latestSweepstake = Sweepstake::latest()->first();

        if (! $latestSweepstake) {
            Notification::make()
                ->danger()
                ->title("Error")
                ->body("No hay sorteos disponibles.")
                ->send();
            return;
        }

        $code = Str::random(12);
        $qrSource = RedemptionSource::where("code", "qr")->first();

        $latestSweepstake->redemptionLinks()->create([
            "redemption_source_id" => $qrSource ? $qrSource->id : null,
            "code" => $code,
            "title" => $data["batch_name"],
            "description" => "Generado desde widget rápido",
            "coupon_count" => $data["coupon_count"],
            "max_redemptions" => $data["max_redemptions"],
            "is_active" => true,
        ]);

        Notification::make()
            ->success()
            ->title("QR generado exitosamente")
            ->body("Se creó el QR para el sorteo {$latestSweepstake->name}")
            ->send();

        $this->form->fill();
    }
}

