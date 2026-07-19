@php
    /** @var \App\Models\Sweepstake $sweepstake */
    $sweepstake = $sweepstake ?? null;
    if (! $sweepstake && isset($record)) {
        $sweepstake = $record;
    }
    $coupons = $sweepstake?->getEligibleCouponsForDraw() ?? collect();
    $total = $coupons->count();

    $payload = $coupons->map(fn (\App\Models\SweepstakeCoupon $c) => [
        'id' => (int) $c->id,
        'number' => (int) $c->coupon_number,
        'label' => '#'.$c->coupon_number,
        'user' => $c->user?->name ?? 'Sin usuario',
    ])->values()->all();
@endphp

<div
    x-data="sweepstakeRoulette({{ json_encode(['coupons' => $payload, 'max' => $total]) }})"
    x-init="$nextTick(() => init())"
    x-cloak
    class="space-y-4 my-2"
>
    @if ($total === 0)
        <div class="rounded-lg bg-amber-50 border border-amber-200 p-4 text-amber-800 dark:bg-amber-900/20 dark:border-amber-800 dark:text-amber-200">
            <p class="font-medium">No hay cupones válidos para sortear.</p>
            <p class="text-sm mt-1">Este sorteo no tiene cupones cobrados (no anulados) disponibles.</p>
        </div>
    @else
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <p class="text-sm text-gray-600 dark:text-gray-400 flex-1">
                Cupones válidos en el sorteo: <strong class="text-gray-900 dark:text-gray-100">{{ $total }}</strong>
                <br />
                <span class="text-xs">La ruleta es una representación visual. Al confirmar, el sistema selecciona los ganadores al azar de forma criptográficamente segura.</span>
            </p>
            <button
                type="button"
                x-on:click="spin()"
                x-bind:disabled="spinning"
                x-bind:class="spinning ? 'opacity-50 cursor-not-allowed' : 'hover:bg-amber-600'"
                class="inline-flex items-center gap-2 rounded-md bg-amber-500 px-4 py-2 text-sm font-semibold text-white shadow transition shrink-0"
            >
                <x-heroicon-o-arrow-path x-show="!spinning" class="w-4 h-4" />
                <x-heroicon-o-arrow-path x-show="spinning" class="animate-spin w-4 h-4" />
                <span x-text="spinning ? 'Girando…' : 'Girar ruleta'"></span>
            </button>
        </div>

        <div class="relative mx-auto" style="max-width: 340px; width: 340px;">
            <div class="selector" style="left: 50%; top: -10px; margin-bottom: -35px;">
                <svg width="32" height="36" viewBox="0 0 32 36" style="filter: drop-shadow(0 2px 3px rgba(0,0,0,0.35)); margin: 0 auto;">
                    <path d="M16 36 L2 6 Q16 -2 30 6 Z" fill="#d97706"/>
                    <circle cx="16" cy="9" r="3.5" fill="#ffffff"/>
                </svg>
            </div>

            <canvas
                x-ref="canvas"
                width="340"
                height="340"
                class="block mx-auto rounded-full shadow-lg bg-white dark:bg-gray-900"
                style="width: 340px; height: 340px;"
            ></canvas>
            <input
                type="text"
                x-ref="winnerPreview"
                value="Presiona Girar ruleta"
                readonly
                class="mt-2 text-center block w-full text-lg font-bold py-2 rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm"
            />
        </div>

        <div x-show="winners.length > 0" x-transition class="mt-2">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-2 flex items-center gap-2">
                🎯 Vista previa de ganadores (referencial)
            </h3>
            <ol class="space-y-2">
                <template x-for="(winner, index) in winners" :key="winner.id">
                    <li class="flex items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 p-2.5 dark:border-amber-800 dark:bg-amber-900/20">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-amber-500 text-white font-bold text-xs" x-text="index + 1"></span>
                        <div class="flex-1">
                            <p class="font-medium text-sm text-gray-900 dark:text-gray-100">
                                Cupón <span x-text="winner.label"></span>
                            </p>
                            <p class="text-xs text-gray-600 dark:text-gray-400" x-text="winner.user"></p>
                        </div>
                    </li>
                </template>
            </ol>
            <p class="text-xs text-gray-500 mt-2 italic">
                Al confirmar, el sistema realizará una nueva selección aleatoria definitiva.
            </p>
        </div>
    @endif
</div>
