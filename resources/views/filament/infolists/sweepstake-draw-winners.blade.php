@php
    /** @var \Illuminate\Support\Collection $state - Collection of SweepstakeCoupon with pivot.position */
    $winners = $state ?? collect();
@endphp

<ol class="space-y-2">
    @foreach ($winners as $coupon)
        <li class="flex items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-900/20">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-500 text-white font-bold text-sm">
                {{ (int) ($coupon->pivot->position ?? 0) }}
            </span>
            <div class="flex-1">
                <p class="font-medium text-gray-900 dark:text-gray-100">
                    Cupón #{{ $coupon->coupon_number }}
                    <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">
                        ({{ $coupon->getDisplayNumber() }})
                    </span>
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    @if ($coupon->user)
                        {{ $coupon->user->name }}
                        @if ($coupon->user->email)
                            · <a href="mailto:{{ $coupon->user->email }}" class="text-amber-600 hover:underline">{{ $coupon->user->email }}</a>
                        @endif
                        @if ($coupon->user->phone)
                            · <a href="tel:{{ $coupon->user->phone }}" class="text-amber-600 hover:underline">{{ $coupon->user->phone }}</a>
                        @endif
                    @else
                        <span class="italic">Sin usuario asociado</span>
                    @endif
                </p>
            </div>
            @if ($coupon->user?->phone)
                <span class="text-xs text-gray-500">📱</span>
            @endif
            @if ($coupon->user?->email)
                <span class="text-xs text-gray-500">✉️</span>
            @endif
        </li>
    @endforeach
</ol>
