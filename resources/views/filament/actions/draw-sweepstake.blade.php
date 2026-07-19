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
                <svg x-show="!spinning" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348H19.5m0 0H23m-3.5 0V5.981m0 3.367A8.985 8.985 0 0 1 12 21c-4.97 0-9-4.03-9-9 0-1.56.398-3.032 1.1-4.313"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.977 14.652H4.5m0 0H1m3.5 0v3.367m0-3.367A8.985 8.985 0 0 1 12 3c4.97 0 9 4.03 9 9 0 1.56-.398 3.032-1.1 4.313"/>
                </svg>
                <svg x-show="spinning" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348H19.5m0 0H23m-3.5 0V5.981m0 3.367A8.985 8.985 0 0 1 12 21c-4.97 0-9-4.03-9-9 0-1.56.398-3.032 1.1-4.313"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.977 14.652H4.5m0 0H1m3.5 0v3.367m0-3.367A8.985 8.985 0 0 1 12 3c4.97 0 9 4.03 9 9 0 1.56-.398 3.032-1.1 4.313"/>
                </svg>
                <span x-text="spinning ? 'Girando…' : 'Girar ruleta'"></span>
            </button>
        </div>

        <div class="relative mx-auto" style="max-width: 340px;">
            <div class="absolute left-1/2 -top-1 z-10 -translate-x-1/2">
                <svg width="28" height="28" viewBox="0 0 32 32" class="text-amber-600 drop-shadow">
                    <path d="M16 28 L4 6 Q16 0 28 6 Z" fill="currentColor"/>
                    <circle cx="16" cy="10" r="3" fill="white"/>
                </svg>
            </div>

            <canvas
                x-ref="canvas"
                width="340"
                height="340"
                class="block mx-auto rounded-full shadow-lg bg-white dark:bg-gray-900"
            ></canvas>
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
