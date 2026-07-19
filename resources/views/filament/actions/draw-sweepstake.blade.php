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
                <svg x-show="!spinning" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.023 9.348h4.992V4.355M3.014 12a9 9 0 1 0 9-9 9 9 0 0 0-6.363 2.636L3 12m6.014-2.652V4.355H4.022"/></svg>
                <svg x-show="spinning" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                <span x-text="spinning ? 'Girando…' : '🎲 Girar ruleta'"></span>
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

@once
    <script>
        window.sweepstakeRoulette = function (config) {
            return {
                coupons: config.coupons || [],
                max: config.max || 0,
                winners: [],
                spinning: false,
                currentAngle: -Math.PI / 2,
                canvas: null,
                ctx: null,
                colors: [
                    '#f59e0b', '#fbbf24', '#fcd34d', '#fde68a',
                    '#d97706', '#b45309', '#92400e', '#78350f',
                    '#f59e0b', '#fbbf24', '#fcd34d', '#fde68a',
                ],

                init() {
                    this.canvas = this.$refs.canvas;
                    if (! this.canvas) return;
                    this.ctx = this.canvas.getContext('2d');
                    this.drawWheel([], this.currentAngle);
                },

                get sliceAngle() {
                    const n = this.coupons.length;
                    return n > 0 ? (2 * Math.PI) / n : 0;
                },

                colorFor(index) {
                    return this.colors[index % this.colors.length];
                },

                drawWheel(winnerIds = [], rotation = -Math.PI / 2) {
                    if (! this.ctx) return;
                    const ctx = this.ctx;
                    const size = this.canvas.width;
                    const center = size / 2;
                    const radius = center - 8;

                    ctx.clearRect(0, 0, size, size);

                    if (this.coupons.length === 0) {
                        ctx.fillStyle = '#e5e7eb';
                        ctx.beginPath();
                        ctx.arc(center, center, radius, 0, 2 * Math.PI);
                        ctx.fill();
                        return;
                    }

                    ctx.save();
                    ctx.translate(center, center);
                    ctx.rotate(rotation);

                    this.coupons.forEach((coupon, i) => {
                        const start = i * this.sliceAngle;
                        const end = start + this.sliceAngle;
                        const isWinner = winnerIds.includes(coupon.id);

                        ctx.beginPath();
                        ctx.moveTo(0, 0);
                        ctx.arc(0, 0, radius, start, end);
                        ctx.closePath();
                        ctx.fillStyle = isWinner ? '#10b981' : this.colorFor(i);
                        ctx.fill();
                        ctx.strokeStyle = '#ffffff';
                        ctx.lineWidth = 1.5;
                        ctx.stroke();

                        if (this.sliceAngle > 0.15) {
                            ctx.save();
                            ctx.rotate(start + this.sliceAngle / 2);
                            ctx.textAlign = 'right';
                            ctx.textBaseline = 'middle';
                            ctx.fillStyle = '#111827';
                            ctx.font = 'bold 12px ui-sans-serif, system-ui, sans-serif';
                            ctx.fillText(coupon.label, radius - 10, 0);
                            ctx.restore();
                        }
                    });

                    ctx.restore();

                    ctx.beginPath();
                    ctx.arc(center, center, 28, 0, 2 * Math.PI);
                    ctx.fillStyle = '#ffffff';
                    ctx.fill();
                    ctx.strokeStyle = '#f59e0b';
                    ctx.lineWidth = 3;
                    ctx.stroke();
                },

                spin() {
                    if (this.spinning || this.coupons.length === 0) return;

                    let winnersCount = 1;
                    try {
                        const input = document.querySelector('input[name="winners_count"]');
                        if (input && input.value) {
                            winnersCount = Math.max(1, parseInt(input.value, 10) || 1);
                        }
                    } catch (e) { /* noop */ }
                    winnersCount = Math.min(winnersCount, this.coupons.length);

                    this.spinning = true;
                    this.winners = [];

                    const indices = Array.from({ length: this.coupons.length }, (_, i) => i);
                    this.shuffle(indices);
                    const winnerIndices = indices.slice(0, winnersCount);
                    const winnerIds = winnerIndices.map((i) => this.coupons[i].id);

                    const targetIndex = winnerIndices[0];
                    const targetSliceCenter = targetIndex * this.sliceAngle + this.sliceAngle / 2;
                    const fullRotations = 5;
                    const finalAngle = -Math.PI / 2 - targetSliceCenter + fullRotations * 2 * Math.PI;

                    const startAngle = this.currentAngle;
                    const delta = finalAngle - startAngle;
                    const duration = 4500;
                    const startTime = performance.now();
                    const easeOutCubic = (t) => 1 - Math.pow(1 - t, 3);

                    const animate = (now) => {
                        const elapsed = now - startTime;
                        const t = Math.min(elapsed / duration, 1);
                        const eased = easeOutCubic(t);
                        const angle = startAngle + delta * eased;
                        this.drawWheel([], angle);
                        if (t < 1) {
                            requestAnimationFrame(animate);
                        } else {
                            this.currentAngle = finalAngle;
                            this.revealWinners(winnerIds);
                        }
                    };

                    requestAnimationFrame(animate);
                },

                revealWinners(winnerIds) {
                    this.winners = this.coupons
                        .filter((c) => winnerIds.includes(c.id))
                        .sort((a, b) => winnerIds.indexOf(a.id) - winnerIds.indexOf(b.id));
                    this.drawWheel(winnerIds, this.currentAngle);
                    this.spinning = false;
                },

                shuffle(arr) {
                    for (let i = arr.length - 1; i > 0; i--) {
                        let j;
                        if (window.crypto && window.crypto.getRandomValues) {
                            const buf = new Uint32Array(1);
                            window.crypto.getRandomValues(buf);
                            j = buf[0] % (i + 1);
                        } else {
                            j = Math.floor(Math.random() * (i + 1));
                        }
                        [arr[i], arr[j]] = [arr[j], arr[i]];
                    }
                    return arr;
                },
            };
        };
    </script>
@endonce
