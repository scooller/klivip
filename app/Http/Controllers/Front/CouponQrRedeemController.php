<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class CouponQrRedeemController extends Controller
{
    public function __invoke(Request $request, string $token): Response
    {
        /** @var Site $site */
        $site = $request->attributes->get('currentSite');

        Log::debug("Attempting to redeem coupon with token: $token for site: {$site->name}");

        $coupon = Coupon::query()
            ->where('site_id', $site->id)
            ->where('qr_enabled', true)
            ->where('qr_token', $token)
            ->first();

        if (! $coupon) {
            return response()->view('coupon-redeem-result', [
                'status' => 'not-found',
                'title' => 'Cupon no encontrado',
                'message' => 'No existe un cupon valido para este codigo QR en este sitio.',
            ], 404);
        }

        if (! $coupon->isValidNow()) {
            Log::debug("Attempting to redeem coupon with token: $token for site: {$site->name} - Coupon is invalid");

            return response()->view('coupon-redeem-result', [
                'status' => 'invalid',
                'title' => 'Cupon no disponible',
                'message' => 'Este cupon no esta activo, expiro o ya alcanzo su limite de usos.',
                'couponCode' => $coupon->code,
                'siteName' => $site->name,
            ], 422);
        }

        $redeemed = Coupon::query()
            ->whereKey($coupon->id)
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
            ->increment('used_count');

        if ($redeemed === 0) {
            Log::debug("Attempting to redeem coupon with token: $token for site: {$site->name} - Coupon could not be redeemed due to concurrent redemption");
            return response()->view('coupon-redeem-result', [
                'status' => 'invalid',
                'title' => 'Cupon no disponible',
                'message' => 'No fue posible cobrar este cupon. Intenta nuevamente.',
                'couponCode' => $coupon->code,
                'siteName' => $site->name,
            ], 422);
        }

        $coupon->refresh();

        return response()->view('coupon-redeem-result', [
            'status' => 'redeemed',
            'title' => 'Cupon cobrado',
            'message' => 'El cupon fue cobrado correctamente.',
            'couponCode' => $coupon->code,
            'siteName' => $site->name,
            'usedCount' => $coupon->used_count,
            'maxUses' => $coupon->max_uses,
        ]);
    }
}
