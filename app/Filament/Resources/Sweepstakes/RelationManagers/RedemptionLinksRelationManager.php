<?php

namespace App\Filament\Resources\Sweepstakes\RelationManagers;

use App\Models\QrBonus;
use App\Models\RedemptionSource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class RedemptionLinksRelationManager extends RelationManager
{
    protected static string $relationship = 'redemptionLinks';

    protected static ?string $title = 'Links/QR';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('redemption_source_id')
                    ->label('Tipo')
                    ->required()
                    ->options(fn () => RedemptionSource::whereIn('code', ['link', 'qr', 'manual'])->pluck('name', 'id')->toArray())
                    ->default(fn () => RedemptionSource::where('code', 'link')->first()?->id)
                    ->selectablePlaceholder(false),
                TextInput::make('code')
                    ->required()
                    ->maxLength(100)
                    ->unique(ignoreRecord: true)
                    ->default(fn () => Str::random(12))
                    ->helperText('Código único del link. Se genera automáticamente si está vacío.'),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Título visible para el usuario (ej: "Pack Fidelidad 10 Cupones")'),
                Textarea::make('description')
                    ->rows(2),
                TextInput::make('coupon_count')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
                    ->label('Cantidad de cupones')
                    ->helperText('Cuántos cupones genera este pack al canjear'),
                TextInput::make('max_redemptions')
                    ->numeric()
                    ->minValue(1)
                    ->label('Máximo de redenciones')
                    ->default(1)
                    ->helperText('Cuántas veces se puede usar este link. Dejar vacío para sin límite'),
                Toggle::make('is_active')
                    ->default(true)
                    ->label('Activo'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('redemptionSource.name')
                    ->label('Tipo')
                    ->searchable(),
                TextColumn::make('coupon_count')
                    ->label('Cupones')
                    ->sortable(),
                TextColumn::make('redemption_count')
                    ->label('Canjes')
                    ->sortable(),
                TextColumn::make('max_redemptions')
                    ->label('Máximo')
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('redemption_source')
                    ->relationship('redemptionSource', 'name')
                    ->label('Tipo de origen'),
            ])
            ->recordActions([
                Action::make('viewQr')
                    ->label('Ver QR')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn ($record) => "QR — {$record->title}")
                    ->modalSubmitActionLabel('Descargar QR')
                    ->modalCancelActionLabel('Cerrar')
                    ->modalContent(function ($record) {
                        $site = $record->sweepstake->site;
                        $redemptionUrl = str_replace(
                            '://',
                            "://{$site->slug}.",
                            config('app.url')
                        )."/redimir/{$record->code}";

                        $qrSvg = QrCode::format('svg')
                            ->size(300)
                            ->margin(2)
                            ->errorCorrection('H')
                            ->generate($redemptionUrl);

                        return view('filament.actions.show-coupon-qr-code', [
                            'qrSvg' => $qrSvg,
                            'url' => $redemptionUrl,
                        ]);
                    })
                    ->action(function ($record) {
                        $site = $record->sweepstake->site;
                        $redemptionUrl = str_replace(
                            '://',
                            "://{$site->slug}.",
                            config('app.url')
                        )."/redimir/{$record->code}";

                        $qrCode = QrCode::format('png')
                            ->size(400)
                            ->margin(10)
                            ->errorCorrection('H')
                            ->generate($redemptionUrl);

                        $filename = "qr-{$record->code}.png";

                        Storage::disk('public')->put("qrs/{$filename}", $qrCode);

                        $downloadUrl = Storage::disk('public')->url("qrs/{$filename}");

                        Notification::make()
                            ->success()
                            ->title('QR generado')
                            ->body('El código QR se ha generado correctamente')
                            ->actions([
                                Action::make('download')
                                    ->label('Descargar')
                                    ->url($downloadUrl)
                                    ->openUrlInNewTab(),
                            ])
                            ->send();
                    }),
                Action::make('downloadQr')
                    ->label('Descargar QR')
                    ->icon('heroicon-o-qr-code')
                    ->color('primary')
                    ->action(function ($record) {
                        $site = $record->sweepstake->site;
                        $redemptionUrl = str_replace(
                            '://',
                            "://{$site->slug}.",
                            config('app.url')
                        )."/redimir/{$record->code}";

                        $qrCode = QrCode::format('png')
                            ->size(400)
                            ->margin(10)
                            ->errorCorrection('H')
                            ->generate($redemptionUrl);

                        $filename = "qr-{$record->code}.png";

                        Storage::disk('public')->put("qrs/{$filename}", $qrCode);

                        $downloadUrl = Storage::disk('public')->url("qrs/{$filename}");

                        Notification::make()
                            ->success()
                            ->title('QR generado')
                            ->body('El código QR se ha generado correctamente')
                            ->actions([
                                Action::make('download')
                                    ->label('Descargar')
                                    ->url($downloadUrl)
                                    ->openUrlInNewTab(),
                            ])
                            ->send();
                    }),
                \Filament\Tables\Actions\DeleteAction::make()
                    ->label('Borrar'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Crear Link/QR')
                    ->icon('heroicon-o-plus')
                    ->schema(fn (Schema $schema): Schema => $schema->components([
                        TextInput::make('quantity')
                            ->label('Cantidad de links/QRs a crear')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5000)
                            ->default(1)
                            ->live()
                            ->helperText('Usa 1 para un link individual, o más para crear un paquete múltiple.'),
                        Select::make('redemption_source_id')
                            ->label('Tipo')
                            ->required()
                            ->options(fn () => RedemptionSource::whereIn('code', ['link', 'qr', 'manual'])->pluck('name', 'id')->toArray())
                            ->default(fn () => RedemptionSource::where('code', 'link')->first()?->id)
                            ->selectablePlaceholder(false),
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->default(fn (\Filament\Resources\RelationManagers\RelationManager $livewire) => $livewire->getOwnerRecord()->name)
                            ->helperText(fn ($get) => intval($get('quantity')) > 1
                                ? 'Se añadirá #1, #2, etc. automáticamente (ej: "Pack Fidelidad #3")'
                                : 'Título visible para el usuario'),
                        Textarea::make('description')
                            ->rows(2),
                        TextInput::make('coupon_count')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->label('Cupones por link')
                            ->helperText('Cuántos cupones genera cada link al canjear'),
                        TextInput::make('max_redemptions')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->label('Máximo de redenciones')
                            ->helperText('Veces que se puede usar cada link. Dejar vacío para sin límite'),
                        Toggle::make('is_active')
                            ->default(true)
                            ->label('Activo'),
                    ]))
                    ->action(function (array $data, CreateAction $action): void {
                        $sweepstake = $this->getOwnerRecord();
                        $quantity = max(1, intval($data['quantity'] ?? 1));
                        $isMultiple = $quantity > 1;

                        for ($i = 0; $i < $quantity; $i++) {
                            $sweepstake->redemptionLinks()->create([
                                'redemption_source_id' => $data['redemption_source_id'],
                                'code' => Str::random(12),
                                'title' => $isMultiple
                                    ? $data['title'].' #'.($i + 1)
                                    : $data['title'],
                                'description' => $data['description'] ?? null,
                                'coupon_count' => $data['coupon_count'],
                                'max_redemptions' => $data['max_redemptions'] ?? null,
                                'is_active' => $data['is_active'] ?? true,
                            ]);
                        }

                        Notification::make()
                            ->success()
                            ->title($isMultiple ? 'Paquete creado' : 'Link/QR creado')
                            ->body($isMultiple
                                ? "Se crearon {$quantity} links/QRs correctamente."
                                : 'Se creó el link/QR correctamente.')
                            ->send();
                    }),
                Action::make('generateQr')
                    ->label('Generar QR')
                    ->icon('heroicon-o-qr-code')
                    ->color('success')
                    ->form([
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
                            ->default(fn (\Filament\Resources\RelationManagers\RelationManager $livewire) => $livewire->getOwnerRecord()->name)
                            ->placeholder('Ej: Evento Julio 2025')
                            ->maxLength(255),
                        TextInput::make('coupon_count')
                            ->label('Cupones por QR')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->helperText('Cupones que genera este QR al canjear'),
                        TextInput::make('max_redemptions')
                            ->label('Máximo de redenciones por QR')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->helperText('Cuántas veces se puede usar este QR'),
                    ])
                    ->action(function (array $data) {
                        $sweepstake = $this->getOwnerRecord();

                        $code = Str::random(12);

                        // Force redemption source to QR
                        $qrSource = RedemptionSource::where('code', 'qr')->first();

                        $sweepstake->redemptionLinks()->create([
                            'redemption_source_id' => $qrSource ? $qrSource->id : null,
                            'code' => $code,
                            'title' => $data['batch_name'],
                            'description' => 'Generado desde admin',
                            'coupon_count' => $data['coupon_count'],
                            'max_redemptions' => $data['max_redemptions'],
                            'is_active' => true,
                        ]);

                        Notification::make()
                            ->success()
                            ->title('QR generado exitosamente')
                            ->body("Se creó el QR '{$data['batch_name']}'")
                            ->send();
                    }),
                Action::make('downloadBatchQr')
                    ->label('Descargar QRs del Filtro')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('warning')
                    ->deselectRecordsAfterCompletion()
                    ->action(function () {
                        $records = $this->getFilteredRecords();

                        if ($records->isEmpty()) {
                            Notification::make()
                                ->warning()
                                ->title('Sin registros')
                                ->body('No hay QRs para descargar en la vista actual')
                                ->send();

                            return;
                        }

                        $zip = new \ZipArchive;
                        $zipFilename = 'qrs-lote-'.now()->format('YmdHis').'.zip';
                        $zipPath = storage_path("app/temp/{$zipFilename}");

                        if ($zip->open($zipPath, \ZipArchive::CREATE) !== true) {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('No se pudo crear el archivo ZIP')
                                ->send();

                            return;
                        }

                        foreach ($records as $record) {
                            $site = $record->sweepstake->site;
                            $redemptionUrl = str_replace(
                                '://',
                                "://{$site->slug}.",
                                config('app.url')
                            )."/redimir/{$record->code}";

                            $qrCode = QrCode::format('png')
                                ->size(400)
                                ->margin(10)
                                ->errorCorrection('H')
                                ->generate($redemptionUrl);

                            $filename = "qr-{$record->code}.png";
                            $zip->addFromString($filename, $qrCode);
                        }

                        $zip->close();

                        Storage::disk('public')->put("qrs/zip/{$zipFilename}", file_get_contents($zipPath));

                        unlink($zipPath);

                        $downloadUrl = Storage::disk('public')->url("qrs/zip/{$zipFilename}");

                        Notification::make()
                            ->success()
                            ->title('ZIP generado')
                            ->body('Se generó el ZIP con '.count($records).' QRs')
                            ->actions([
                                Action::make('download')
                                    ->label('Descargar ZIP')
                                    ->url($downloadUrl)
                                    ->openUrlInNewTab(),
                            ])
                            ->send();
                    }),
            ]);
    }
}
