<?php

namespace App\Http\Controllers\Front;

use App\Enums\CouponType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Site;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function updateProfile(Request $request): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        if (! $customer instanceof User) {
            return back()->withErrors([
                'profile' => 'No encontramos una sesion valida para actualizar el perfil.',
            ]);
        }

        $adultLimitDate = CarbonImmutable::now()->subYears(18)->toDateString();

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc,dns', 'max:255', 'confirmed', Rule::unique('users', 'email')->ignore($customer->id)],
            'phone' => ['required', 'string', 'max:25'],
            'birth_date' => ['required', 'date', 'before_or_equal:'.$adultLimitDate],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ], [
            'email.confirmed' => 'El correo y la confirmacion de correo deben coincidir.',
            'birth_date.before_or_equal' => 'Debes ser mayor de 18 anos.',
            'avatar.image' => 'El avatar debe ser una imagen valida.',
            'avatar.max' => 'El avatar no debe superar los 2 MB.',
        ]);

        $normalizedPhone = $this->normalizePhone((string) $payload['phone']);

        if ($normalizedPhone === null) {
            return back()->withErrors([
                'phone' => 'Ingresa un numero de telefono valido.',
            ]);
        }

        $phoneExists = User::query()
            ->where('phone', $normalizedPhone)
            ->whereKeyNot($customer->id)
            ->exists();

        if ($phoneExists) {
            return back()->withErrors([
                'phone' => 'Este numero de telefono ya esta registrado.',
            ]);
        }

        $avatarPath = $customer->avatar_path;

        if ($request->hasFile('avatar')) {
            if ($avatarPath !== null) {
                Storage::disk('public')->delete($avatarPath);
            }

            $avatarPath = $request->file('avatar')?->store('avatars', 'public');
        }

        $customer->update([
            'name' => trim((string) $payload['name']),
            'email' => mb_strtolower(trim((string) $payload['email'])),
            'phone' => $normalizedPhone,
            'birth_date' => $payload['birth_date'],
            'avatar_path' => $avatarPath,
        ]);

        return back()->with('customer_profile_status', 'updated');
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        /** @var Site $site */
        $site = $request->attributes->get('currentSite');
        $customer = Auth::guard('customer')->user();
        $otpLogin = [
            'pending' => $request->session()->has('customer_otp_email'),
            'identifier' => $request->session()->get('customer_otp_identifier'),
            'email' => $request->session()->get('customer_otp_email'),
            'expiresAt' => $request->session()->get('customer_otp_expires_at'),
        ];
        $configuredAdminDomain = (string) config('app.admin_domain');
        $normalizedAdminDomain = parse_url($configuredAdminDomain, PHP_URL_HOST) ?: $configuredAdminDomain;
        $adminPortal = [
            'url' => sprintf('%s://%s', $request->getScheme(), $normalizedAdminDomain),
        ];
        $activeCoupons = $this->resolveActiveCoupons($site);

        return Inertia::render('User', [
            'site' => [
                'name' => $site->name,
                'slug' => $site->slug,
                'address' => $site->address,
                'opening_hours' => $site->opening_hours,
            ],
            'auth' => [
                'customer' => $customer ? [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'birth_date' => $customer->birth_date?->format('Y-m-d'),
                    'avatar_url' => $this->resolveAvatarUrl($customer->avatar_path),
                    'role' => UserRole::User->value,
                ] : null,
                'adminPortal' => $adminPortal,
                'otpLogin' => $otpLogin,
            ],
            'activeCoupons' => $activeCoupons,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function resolveActiveCoupons(Site $site): array
    {
        return Coupon::query()
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
            ->limit(20)
            ->get()
            ->map(function (Coupon $coupon) use ($site): array {
                $typeLabel = $coupon->type instanceof CouponType
                    ? $coupon->type->label()
                    : (string) $coupon->type;

                return [
                    'id' => $coupon->id,
                    'site_name' => $site->name,
                    'code' => $coupon->code,
                    'type_label' => $typeLabel,
                    'draw_label' => mb_strtoupper($typeLabel),
                    'valid_to' => $coupon->valid_to?->format('d/m/Y - H:i'),
                ];
            })
            ->values()
            ->all();
    }

    private function normalizePhone(string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if (mb_strlen($digits) < 8) {
            return null;
        }

        return '+'.$digits;
    }

    private function resolveAvatarUrl(?string $avatarPath): ?string
    {
        if ($avatarPath === null || $avatarPath === '') {
            return null;
        }

        return asset('storage/'.$avatarPath);
    }
}
