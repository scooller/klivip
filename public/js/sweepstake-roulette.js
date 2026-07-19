/*!
 * Sweepstake Roulette - Alpine component for Filament draw modal.
 * Loaded globally on admin panel so it's available in Livewire modals.
 */
document.addEventListener('alpine:init', () => {
    if (!window.Alpine) return;

    window.Alpine.data('sweepstakeRoulette', (config) => ({
        coupons: (config && config.coupons) || [],
        max: (config && config.max) || 0,
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
            if (!this.canvas) return;
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
            if (!this.ctx) return;
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

            // Preview shuffle: muestra cupones al azar cada cierto intervalo
            const previewEl = this.$refs.winnerPreview;
            let lastShuffleAt = 0;
            let lastPreviewIdx = -1;
            const updatePreview = (forceIdx = null) => {
                if (!previewEl) return;
                if (forceIdx !== null) {
                    const c = this.coupons[forceIdx];
                    previewEl.value = c ? c.label : '';
                    return;
                }
                let idx;
                let attempts = 0;
                do {
                    idx = Math.floor(Math.random() * this.coupons.length);
                    attempts++;
                } while (idx === lastPreviewIdx && attempts < 5 && this.coupons.length > 1);
                lastPreviewIdx = idx;
                const c = this.coupons[idx];
                if (c) previewEl.value = c.label;
            };
            updatePreview();

            const animate = (now) => {
                const elapsed = now - startTime;
                const t = Math.min(elapsed / duration, 1);
                const eased = easeOutCubic(t);
                const angle = startAngle + delta * eased;
                this.drawWheel([], angle);

                // Preview: actualiza con menor frecuencia a medida que desacelera
                // Intervalo dinamico: rapido al inicio, lento al final
                const interval = 50 + Math.pow(t, 3) * 350; // 50ms -> 400ms
                if (t < 1 && elapsed - lastShuffleAt >= interval) {
                    updatePreview();
                    lastShuffleAt = elapsed;
                }

                if (t < 1) {
                    requestAnimationFrame(animate);
                } else {
                    this.currentAngle = finalAngle;
                    updatePreview(targetIndex);
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
    }));
});
