<x-filament-widgets::widget>
    <x-filament::section>
        <div class="mb-4">
            <h2 class="text-lg font-bold">Crear QR Rápido para el último sorteo</h2>
        </div>
        <form wire:submit="generateQr">
            {{ $this->form }}
            <div class="mt-4 text-right">
                <x-filament::button type="submit" color="success">
                    Generar QR
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</x-filament-widgets::widget>
