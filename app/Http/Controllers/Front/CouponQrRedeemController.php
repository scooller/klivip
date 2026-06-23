<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CouponQrRedeemController extends Controller
{
    public function __invoke(Request $request, string $token): Response
    {
        /** @var Site $site */
        $site = $request->attributes->get('currentSite');
        $qr_token = $request->route('token');
        $customer = Auth::guard('customer')->user();

        Log::info("Attempting to redeem coupon with token: {$qr_token} for site: {$site->name} (site_id: {$site->id})");
        Log::debug('Route parameters', $request->route()?->parameters() ?? []);

        $coupon = Coupon::query()
            ->where('site_id', $site->id)
            ->where('qr_enabled', true)
            ->where('qr_token', $qr_token)
            ->first();

        Log::info('Coupon query result', ['coupon_id' => $coupon?->id, 'coupon_code' => $coupon?->code]);

        if (! $coupon) {
            return response()->view('coupon-redeem-result', [
                'status' => 'not-found',
                'title' => 'Cupón no encontrado',
                'message' => 'No existe un cupón válido para este código QR en este sitio.',
            ], 404);
        }

        if ($customer instanceof User && $coupon->users()->whereKey($customer->id)->whereNotNull('redeemed_at')->exists()) {
            return response()->view('coupon-redeem-result', [
                'status' => 'already-redeemed',
                'title' => 'Cupón ya cobrado',
                'message' => 'Ya has cobrado este cupón anteriormente.',
                'couponCode' => $coupon->code,
                'siteName' => $site->name,
            ], 422);
        }

        if ($coupon->max_uses !== null && $coupon->used_count >= $coupon->max_uses) {
            return response()->view('coupon-redeem-result', [
                'status' => 'max-uses-reached',
                'title' => 'Cupón agotado',
                'message' => 'Este cupón ya alcanzó el límite máximo de usos.',
                'couponCode' => $coupon->code,
                'siteName' => $site->name,
                'usedCount' => $coupon->used_count,
                'maxUses' => $coupon->max_uses,
            ], 422);
        }

        if (! $coupon->isValidNow()) {
            Log::debug("Attempting to redeem coupon with token: $qr_token for site: {$site->name} - Coupon is invalid");

            return response()->view('coupon-redeem-result', [
                'status' => 'invalid',
                'title' => 'Cupón no disponible',
                'message' => 'Este cupón no está activo o expiró.',
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
            Log::debug("Attempting to redeem coupon with token: $qr_token for site: {$site->name} - Coupon could not be redeemed due to concurrent redemption");

            return response()->view('coupon-redeem-result', [
                'status' => 'invalid',
                'title' => 'Cupón no disponible',
                'message' => 'No fue posible cobrar este cupón. Intenta nuevamente.',
                'couponCode' => $coupon->code,
                'siteName' => $site->name,
            ], 422);
        }

        if ($customer instanceof User) {
            $redeemCode = 'KV-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(4));
            $coupon->users()->syncWithoutDetaching([
                $customer->id => [
                    'redeemed_at' => now(),
                    'redeem_code' => $redeemCode,
                ],
            ]);
        }

        $coupon->refresh();

        $response = [
            'status' => 'redeemed',
            'title' => 'Cupón cobrado',
            'message' => 'El cupón fue cobrado correctamente.',
            'couponCode' => $coupon->code,
            'siteName' => $site->name,
            'usedCount' => $coupon->used_count,
            'maxUses' => $coupon->max_uses,
        ];

        if ($customer instanceof User) {
            $response['redeemCode'] = $redeemCode;
        }

        return response()->view('coupon-redeem-result', $response);
    }
}
