<?php

namespace App\Http\Controllers\Front;

use App\Enums\CouponType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class UserCouponsController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var Site $site */
        $site = $request->attributes->get('currentSite');
        $customer = Auth::guard('customer')->user();

        $paginatedCoupons = Coupon::query()
            ->where('site_id', $site->id)
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNull('valid_from')->orWhere('valid_from', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('valid_to')->orWhere('valid_to', '>=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('max_uses')->orWhereColumn('used_count', '<', 'max_uses');
            })
            ->orderBy('valid_to')
            ->orderByDesc('created_at')
            ->paginate(4)
            ->withQueryString();

        $activeCoupons = $paginatedCoupons
            ->getCollection()
            ->map(fn(Coupon $coupon): array => $this->mapCoupon($coupon, $site))
            ->values()
            ->all();

        return Inertia::render('UserCouponsIndex', [
            'site' => [
                'name' => $site->name,
                'slug' => $site->slug,
                'logo' => $site->logo ? asset('storage/' . $site->logo) : null,
                'address' => $site->address,
                'opening_hours' => $site->opening_hours,
                'links' => $site->links,
            ],
            'auth' => [
                'customer' => $customer ? [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'role' => UserRole::User->value,
                ] : null,
            ],
            'activeCoupons' => $activeCoupons,
            'pagination' => [
                'current_page' => $paginatedCoupons->currentPage(),
                'last_page' => $paginatedCoupons->lastPage(),
                'per_page' => $paginatedCoupons->perPage(),
                'total' => $paginatedCoupons->total(),
                'prev_page_url' => $paginatedCoupons->previousPageUrl(),
                'next_page_url' => $paginatedCoupons->nextPageUrl(),
            ],
        ]);
    }

    public function show(Request $request, string $couponId): Response
    {
        /** @var Site $site */
        $site = $request->attributes->get('currentSite');
        $customer = Auth::guard('customer')->user();

        $coupon = Coupon::query()
            ->where('id', (int) $couponId)
            ->where('site_id', $site->id)
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNull('valid_from')->orWhere('valid_from', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('valid_to')->orWhere('valid_to', '>=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('max_uses')->orWhereColumn('used_count', '<', 'max_uses');
            })
            ->first();

        if ($coupon === null) {
            $coupon = Coupon::query()
                ->where('site_id', $site->id)
                ->where('is_active', true)
                ->where(function ($query): void {
                    $query->whereNull('valid_from')->orWhere('valid_from', '<=', now());
                })
                ->where(function ($query): void {
                    $query->whereNull('valid_to')->orWhere('valid_to', '>=', now());
                })
                ->where(function ($query): void {
                    $query->whereNull('max_uses')->orWhereColumn('used_count', '<', 'max_uses');
                })
                ->orderBy('valid_to')
                ->orderByDesc('created_at')
                ->first();
        }

        return Inertia::render('UserCouponShow', [
            'site' => [
                'name' => $site->name,
                'slug' => $site->slug,
                'logo' => $site->logo ? asset('storage/' . $site->logo) : null,
                'address' => $site->address,
                'opening_hours' => $site->opening_hours,
                'links' => $site->links,
            ],
            'auth' => [
                'customer' => $customer ? [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'role' => UserRole::User->value,
                ] : null,
            ],
            'coupon' => $coupon ? $this->mapCoupon($coupon, $site) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapCoupon(Coupon $coupon, Site $site): array
    {
        $typeLabel = $coupon->type instanceof CouponType
            ? $coupon->type
            : (string) $coupon->type->label();

        return [
            'id' => $coupon->id,
            'site_name' => $site->name,
            'code' => $coupon->code,
            'type_label' => $typeLabel,
            'draw_label' => mb_strtoupper($typeLabel),
            'valid_from' => $coupon->valid_from?->format('d/m/Y - H:i'),
            'valid_to' => $coupon->valid_to?->format('d/m/Y - H:i'),
            'message' => $coupon->message,
            'value' => $coupon->value,
            'max_uses' => $coupon->max_uses,
        ];
    }
}
