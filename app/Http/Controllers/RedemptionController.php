<?php

namespace App\Http\Controllers;

use App\Exceptions\RedemptionException;
use App\Http\Requests\RedeemCouponRequest;
use App\Models\RedemptionLink;
use App\Services\RedemptionService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RedemptionController extends Controller
{
    public function __construct(
        private RedemptionService $redemptionService
    ) {}

    public function show(string $site, string $code, Request $request)
    {
        try {
            $link = RedemptionLink::with(['sweepstake', 'redemptionSource'])
                ->where('code', $code)
                ->firstOrFail();

            if (! $link->isAvailable()) {
                return inertia('Redemption/Unavailable', [
                    'reason' => $this->getUnavailabilityReason($link),
                ]);
            }

            return inertia('Redemption/Show', [
                'link' => [
                    'code' => $link->code,
                    'title' => $link->title,
                    'description' => $link->description,
                    'coupon_count' => $link->coupon_count,
                ],
                'sweepstake' => [
                    'name' => $link->sweepstake->name,
                    'description' => $link->sweepstake->description,
                    'prize' => $link->sweepstake->prize_description,
                    'expires_at' => $link->sweepstake->expires_at->format('d/m/Y H:i'),
                ],
                'customer' => $request->user('customer') ? [
                    'name' => $request->user('customer')->name,
                    'email' => $request->user('customer')->email,
                ] : null,
            ]);
        } catch (ModelNotFoundException $e) {
            abort(404, 'Link de canje no encontrado');
        }
    }

    public function redeem(string $site, string $code, RedeemCouponRequest $request)
    {
        try {
            $customer = $request->user('customer');

            $redemption = $this->redemptionService->redeem(
                linkCode: $code,
                userEmail: $customer->email,
                userPhone: $customer->phone,
                userName: $customer->name,
                authenticatedUser: $customer,
                metadata: [
                    'channel' => $request->validated('channel') ?? 'qr',
                    'device_info' => $this->getDeviceInfo($request),
                ]
            );

            return inertia('Redemption/Success', [
                'redemption' => [
                    'coupon_count' => $redemption->coupon_count,
                    'coupon_numbers' => $redemption->getCouponNumbers(),
                    'sweepstake_name' => $redemption->sweepstake->name,
                    'sweepstake_slug' => $redemption->sweepstake->slug,
                ],
            ]);
        } catch (RedemptionException $e) {
            $customer = $request->user('customer');

            Log::warning('Coupon redemption failed', [
                'code' => $code,
                'error' => $e->getMessage(),
                'user' => $customer?->email ?? $customer?->phone ?? $customer?->id ?? 'unknown',
            ]);

            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Unexpected error during redemption', [
                'code' => $code,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Ocurrió un error inesperado. Por favor intenta nuevamente.');
        }
    }

    protected function getUnavailabilityReason(RedemptionLink $link): string
    {
        if (! $link->is_active) {
            return 'Este link ha sido desactivado';
        }

        if ($link->max_redemptions && $link->redemption_count >= $link->max_redemptions) {
            return 'Este link ha alcanzado su límite de redenciones';
        }

        $sweepstake = $link->sweepstake;

        if (! $sweepstake->is_active) {
            return 'El sorteo ha sido desactivado';
        }

        if (! $sweepstake->is_published) {
            return 'El sorteo no está publicado';
        }

        if ($sweepstake->starts_at?->isFuture()) {
            return 'El sorteo comienza el '.$sweepstake->starts_at->format('d/m/Y H:i');
        }

        if ($sweepstake->expires_at?->isPast()) {
            return 'El sorteo finalizó el '.$sweepstake->expires_at->format('d/m/Y H:i');
        }

        if (! $sweepstake->hasAvailableSlots()) {
            return 'El sorteo no tiene más cupos disponibles';
        }

        return 'No disponible';
    }

    protected function getDeviceInfo(Request $request): array
    {
        return [
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'is_mobile' => $request->header('User-Agent') && preg_match('/mobile/i', $request->header('User-Agent')),
        ];
    }
}
