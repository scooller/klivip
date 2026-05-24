<div class="space-y-4">
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

        <div class="flex justify-center">
            @if (! empty($qrDownloadUrl))
                <a
                    href="{{ $qrDownloadUrl }}"
                    download="{{ $qrDownloadName ?? 'coupon-qr.svg' }}"
                    class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200"
                >
                    Descargar QR
                </a>
            @endif
        </div>
    @endif

    <div class="space-y-2">
        <p class="text-sm font-medium text-gray-950 dark:text-white">URL</p>

        @if (! empty($url))
            <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm break-all text-gray-700 dark:border-white/10 dark:bg-white/5 dark:text-gray-200">
                {{ $url }}
            </div>
        @elseif(! empty($message))
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:border-amber-600/40 dark:bg-amber-600/10 dark:text-amber-200">
                {{ $message }}
            </div>
        @endif
    </div>
</div>
