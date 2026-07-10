<?php

namespace App\Http\Controllers\Front;

use App\Exceptions\RedemptionException;
use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\SweepstakeCoupon;
use App\Models\User;
use App\Services\RedemptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class UserSweepstakeCouponsController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var Site $site */
        $site = $request->attributes->get('currentSite');
        $customer = Auth::guard('customer')->user();

        $coupons = SweepstakeCoupon::query()
            ->with(['sweepstake:id,name,slug,draw_at,prize_description'])
            ->where('user_id', $customer->id)
            ->where('is_voided', false)
            ->orderByDesc('created_at')
            ->paginate(12)
            ->through(fn (SweepstakeCoupon $coupon): array => $this->mapCoupon($coupon));

        $grouped = collect($coupons->items())
            ->groupBy(fn (array $coupon): string => $coupon['sweepstake_slug'])
            ->map(fn (Collection $items): array => [
                'sweepstake_name' => $items->first()['sweepstake_name'],
                'sweepstake_slug' => $items->first()['sweepstake_slug'],
                'prize' => $items->first()['prize'],
                'draw_at' => $items->first()['draw_at'],
                'draw_at_date' => $items->first()['draw_at_date'],
                'coupons' => $items->map(fn (array $c): array => [
                    'id' => $c['id'],
                    'number' => $c['number'],
                    'is_used' => $c['is_used'],
                    'obtained_at' => $c['obtained_at'],
                ])->values()->all(),
            ])
            ->sortBy('draw_at_date')
            ->values()
            ->all();

        return Inertia::render('UserCouponsIndex', [
            'site' => $this->mapSite($site),
            'groupedCoupons' => $grouped,
            'pagination' => [
                'current_page' => $coupons->currentPage(),
                'last_page' => $coupons->lastPage(),
                'total' => $coupons->total(),
                'prev_page_url' => $coupons->previousPageUrl(),
                'next_page_url' => $coupons->nextPageUrl(),
            ],
            'auth' => [
                'customer' => $customer ? [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                ] : null,
            ],
        ]);
    }

    public function redeemByCode(Request $request, RedemptionService $redemptionService): RedirectResponse
    {
        /** @var User $customer */
        $customer = Auth::guard('customer')->user();

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:255'],
        ], [
            'code.required' => 'Debes ingresar un código de canje.',
        ]);

        try {
            $redemption = $redemptionService->redeem(
                linkCode: $validated['code'],
                userEmail: $customer->email,
                userPhone: $customer->phone,
                userName: $customer->name,
                authenticatedUser: $customer,
                metadata: [
                    'channel' => 'customer-form',
                    'device_info' => [
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ],
                ],
            );

            return back()->with('redeemSuccess', [
                'coupon_count' => $redemption->coupon_count,
                'coupon_numbers' => $redemption->getCouponNumbers(),
                'sweepstake_name' => $redemption->sweepstake->name,
            ]);
        } catch (RedemptionException $e) {
            Log::warning('Customer code redemption failed', [
                'code' => $validated['code'],
                'error' => $e->getMessage(),
                'user_id' => $customer->id,
            ]);

            return back()->withErrors(['code' => $e->getMessage()]);
        } catch (Throwable $e) {
            Log::error('Unexpected error during customer code redemption', [
                'code' => $validated['code'],
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['code' => 'Ocurrió un error inesperado. Intenta nuevamente.']);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function mapCoupon(SweepstakeCoupon $coupon): array
    {
        $sweepstake = $coupon->sweepstake;

        return [
            'id' => $coupon->id,
            'number' => $coupon->coupon_number,
            'display_number' => $coupon->getDisplayNumber(),
            'is_used' => $coupon->is_used,
            'is_voided' => $coupon->is_voided,
            'obtained_at' => $coupon->created_at?->format('d/m/Y H:i'),
            'sweepstake_name' => $sweepstake?->name ?? 'Sorteo',
            'sweepstake_slug' => $sweepstake?->slug ?? '',
            'prize' => $sweepstake?->prize_description,
            'draw_at' => $sweepstake?->draw_at?->format('d/m/Y H:i'),
            'draw_at_date' => $sweepstake?->draw_at,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapSite(Site $site): array
    {
        return [
            'name' => $site->name,
            'slug' => $site->slug,
            'logo' => $site->logo ? asset('storage/'.$site->logo) : null,
            'content' => $site->content,
            'address' => $site->address,
            'opening_hours' => $site->opening_hours,
            'links' => $site->links,
        ];
    }
}
