<div
    class="space-y-4"
    x-data="{ copied: false }"
>
    @if (! empty($qrSvg))
        @php
            $isQrDataUri = is_string($qrSvg) && str_starts_with($qrSvg, 'data:image');
        @endphp

        <div class="flex justify-center rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
            @if ($isQrDataUri)
                <img src="{{ $qrSvg }}" alt="Codigo QR del cupon" class="h-auto max-w-full" />
            @else
                {!! $qrSvg !!}
            @endif
        </div>
    @endif

    <div class="space-y-2">
        <p class="text-sm font-medium text-gray-950 dark:text-white">URL de redencion</p>

        @if (! empty($url))
            <div class="flex items-center gap-2">
                <div class="flex-1 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm break-all text-gray-700 dark:border-white/10 dark:bg-white/5 dark:text-gray-200">
                    {{ $url }}
                </div>
                <button
                    type="button"
                    @click="
                        navigator.clipboard.writeText('{{ $url }}');
                        copied = true;
                        setTimeout(() => copied = false, 2000);
                    "
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white transition hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200"
                >
                    <span x-show="!copied">Copiar</span>
                    <span x-show="copied" x-cloak>Copiado!</span>
                </button>
            </div>
        @elseif(! empty($message))
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:border-amber-600/40 dark:bg-amber-600/10 dark:text-amber-200">
                {{ $message }}
            </div>
        @endif
    </div>
</div>
