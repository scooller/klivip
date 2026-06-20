<?php

namespace App\Http\Controllers\Front;

use App\Enums\CouponType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Site;
use App\Models\SiteSetting;
use App\Models\User;
use App\Notifications\FrontProfileUnlockLinkNotification;
use App\Notifications\FrontProfileUnlockOtpNotification;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class UserController extends Controller
{
    private const PROFILE_UNLOCK_SESSION_KEY = 'customer_profile_unlock_until';

    private const PROFILE_UNLOCK_DURATION_MINUTES = 10;

    private const PROFILE_UNLOCK_OTP_DURATION_MINUTES = 10;

    private const PROFILE_UNLOCK_LINK_DURATION_MINUTES = 15;

    public function updateProfile(Request $request): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        if (! $customer instanceof User) {
            return back()->withErrors([
                'profile' => 'No encontramos una sesion valida para actualizar el perfil.',
            ]);
        }

        if (! $this->hasActiveProfileUnlock($request)) {
            return back()->withErrors([
                'profile' => 'Debes desbloquear el perfil antes de editarlo.',
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

        $request->session()->forget(self::PROFILE_UNLOCK_SESSION_KEY);

        return back()->with('customer_profile_status', 'updated');
    }

    public function requestProfileUnlockOtp(Request $request): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        if (! $customer instanceof User) {
            return back()->withErrors([
                'profile_unlock' => 'No encontramos una sesion valida.',
            ]);
        }

        if (! (bool) SiteSetting::get('enable_profile_unlock_otp', true)) {
            return back()->withErrors([
                'profile_unlock' => 'El desbloqueo con codigo no esta disponible.',
            ]);
        }

        $site = $this->resolveSite($request);
        $requestKey = $this->profileUnlockOtpRequestKey($customer->id, $site->slug);

        if (RateLimiter::tooManyAttempts($requestKey, 1)) {
            return back()->withErrors([
                'profile_unlock' => 'Espera un momento antes de solicitar otro codigo.',
            ]);
        }

        RateLimiter::hit($requestKey, 60);

        $otpCode = Str::upper(Str::random(6));
        $cacheKey = $this->profileUnlockOtpCacheKey($customer->id, $site->slug);

        Cache::put($cacheKey, [
            'code_hash' => Hash::make($otpCode),
        ], now()->addMinutes(self::PROFILE_UNLOCK_OTP_DURATION_MINUTES));

        try {
            $customer->notify(new FrontProfileUnlockOtpNotification($otpCode, $site->name));
        } catch (TransportExceptionInterface $exception) {
            Cache::forget($cacheKey);
            RateLimiter::clear($requestKey);

            return back()->withErrors([
                'profile_unlock' => 'No se pudo enviar el codigo de desbloqueo. Intenta nuevamente.',
            ]);
        }

        return back()->with('customer_profile_unlock_status', 'otp_sent');
    }

    public function verifyProfileUnlockOtp(Request $request): RedirectResponse
    {
        if (! (bool) SiteSetting::get('enable_profile_unlock_otp', true)) {
            return back()->withErrors([
                'profile_unlock_otp' => 'El desbloqueo con codigo no esta disponible.',
            ]);
        }

        $payload = $request->validate([
            'otp_code' => ['required', 'alpha_num:6'],
        ]);

        $customer = Auth::guard('customer')->user();

        if (! $customer instanceof User) {
            return back()->withErrors([
                'profile_unlock_otp' => 'No encontramos una sesion valida.',
            ]);
        }

        $site = $this->resolveSite($request);
        $cacheKey = $this->profileUnlockOtpCacheKey($customer->id, $site->slug);
        $otpPayload = Cache::get($cacheKey);

        if (! is_array($otpPayload) || ! isset($otpPayload['code_hash']) || ! Hash::check((string) $payload['otp_code'], (string) $otpPayload['code_hash'])) {
            return back()->withErrors([
                'profile_unlock_otp' => 'El codigo no es valido o ya expiro.',
            ]);
        }

        Cache::forget($cacheKey);
        RateLimiter::clear($this->profileUnlockOtpRequestKey($customer->id, $site->slug));
        $this->activateProfileUnlock($request);

        return back()->with('customer_profile_unlock_status', 'unlocked');
    }

    public function requestProfileUnlockLink(Request $request): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        if (! $customer instanceof User) {
            return back()->withErrors([
                'profile_unlock' => 'No encontramos una sesion valida.',
            ]);
        }

        if (! (bool) SiteSetting::get('enable_profile_unlock_magic_link', true)) {
            return back()->withErrors([
                'profile_unlock' => 'El desbloqueo con enlace no esta disponible.',
            ]);
        }

        $site = $this->resolveSite($request);
        $requestKey = $this->profileUnlockLinkRequestKey($customer->id, $site->slug);

        if (RateLimiter::tooManyAttempts($requestKey, 1)) {
            return back()->withErrors([
                'profile_unlock' => 'Espera un momento antes de solicitar otro enlace.',
            ]);
        }

        RateLimiter::hit($requestKey, 60);

        $rawToken = Str::random(64);
        $cacheKey = $this->profileUnlockLinkCacheKey($customer->id, $site->slug);

        Cache::put($cacheKey, [
            'token_hash' => Hash::make($rawToken),
        ], now()->addMinutes(self::PROFILE_UNLOCK_LINK_DURATION_MINUTES));

        $currentRouteName = (string) $request->route()?->getName();
        $unlockRouteName = str_replace('.request', '', $currentRouteName);
        $unlockUrl = URL::temporarySignedRoute(
            $unlockRouteName,
            now()->addMinutes(self::PROFILE_UNLOCK_LINK_DURATION_MINUTES),
            ['token' => $rawToken],
        );

        try {
            $customer->notify(new FrontProfileUnlockLinkNotification($unlockUrl, $site->name));
        } catch (TransportExceptionInterface $exception) {
            Cache::forget($cacheKey);
            RateLimiter::clear($requestKey);

            return back()->withErrors([
                'profile_unlock' => 'No se pudo enviar el enlace de desbloqueo. Intenta nuevamente.',
            ]);
        }

        return back()->with('customer_profile_unlock_status', 'link_sent');
    }

    public function consumeProfileUnlockLink(Request $request, string $token): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            return redirect('/usuario')->withErrors([
                'profile_unlock' => 'El enlace de desbloqueo no es valido o ya expiro.',
            ]);
        }

        if (! (bool) SiteSetting::get('enable_profile_unlock_magic_link', true)) {
            return redirect('/usuario')->withErrors([
                'profile_unlock' => 'El desbloqueo con enlace no esta disponible.',
            ]);
        }

        $customer = Auth::guard('customer')->user();

        if (! $customer instanceof User) {
            return redirect('/usuario')->withErrors([
                'profile_unlock' => 'No encontramos una sesion valida.',
            ]);
        }

        $site = $this->resolveSite($request);
        $cacheKey = $this->profileUnlockLinkCacheKey($customer->id, $site->slug);
        $linkPayload = Cache::get($cacheKey);

        if (! is_array($linkPayload) || ! isset($linkPayload['token_hash']) || ! Hash::check($token, (string) $linkPayload['token_hash'])) {
            return redirect('/usuario')->withErrors([
                'profile_unlock' => 'El enlace de desbloqueo ya fue usado o es invalido.',
            ]);
        }

        Cache::forget($cacheKey);
        RateLimiter::clear($this->profileUnlockLinkRequestKey($customer->id, $site->slug));
        $this->activateProfileUnlock($request);

        return redirect('/usuario')->with('customer_profile_unlock_status', 'unlocked');
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        /** @var Site $site */
        $site = $request->attributes->get('currentSite');
        $customer = Auth::guard('customer')->user();
        $settings = SiteSetting::current();
        $loginRequiresOtp = ! (bool) $settings->enable_home_login_without_code;
        $isProfileUnlocked = $customer instanceof User && $this->hasActiveProfileUnlock($request);
        $otpLogin = [
            'pending' => $loginRequiresOtp && $request->session()->has('customer_otp_email'),
            'identifier' => $loginRequiresOtp ? $request->session()->get('customer_otp_identifier') : null,
            'email' => $loginRequiresOtp ? $request->session()->get('customer_otp_email') : null,
            'expiresAt' => $loginRequiresOtp ? $request->session()->get('customer_otp_expires_at') : null,
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
                'logo' => $site->logo ? asset('storage/'.$site->logo) : null,
                'address' => $site->address,
                'opening_hours' => $site->opening_hours,
                'links' => $site->links,
            ],
            'auth' => [
                'customer' => $customer instanceof User
                    ? $this->resolveCustomerPayload($customer, $isProfileUnlocked, (bool) $settings->hide_birth_date_on_profile)
                    : null,
                'adminPortal' => $adminPortal,
                'otpLogin' => $otpLogin,
                'security' => [
                    'loginRequiresOtp' => $loginRequiresOtp,
                    'profileUnlock' => [
                        'unlocked' => $isProfileUnlocked,
                        'otpEnabled' => (bool) $settings->enable_profile_unlock_otp,
                        'magicLinkEnabled' => (bool) $settings->enable_profile_unlock_magic_link,
                        'hideBirthDate' => (bool) $settings->hide_birth_date_on_profile,
                    ],
                ],
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
                    'message' => $coupon->message,
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

    /**
     * @return array<string, mixed>
     */
    private function resolveCustomerPayload(User $customer, bool $isUnlocked, bool $hideBirthDate): array
    {
        $email = $isUnlocked
            ? $customer->email
            : $this->maskEmail($customer->email);

        $phone = $isUnlocked
            ? (string) $customer->phone
            : $this->maskPhone((string) $customer->phone);

        $birthDate = $isUnlocked
            ? $customer->birth_date?->format('Y-m-d')
            : (! $hideBirthDate ? $customer->birth_date?->format('Y-m-d') : null);

        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $email,
            'phone' => $phone,
            'birth_date' => $birthDate,
            'avatar_url' => $this->resolveAvatarUrl($customer->avatar_path),
            'role' => UserRole::User->value,
        ];
    }

    private function activateProfileUnlock(Request $request): void
    {
        $request->session()->put(
            self::PROFILE_UNLOCK_SESSION_KEY,
            now()->addMinutes(self::PROFILE_UNLOCK_DURATION_MINUTES)->timestamp,
        );
    }

    private function hasActiveProfileUnlock(Request $request): bool
    {
        $unlockUntil = (int) $request->session()->get(self::PROFILE_UNLOCK_SESSION_KEY, 0);

        if ($unlockUntil <= now()->timestamp) {
            $request->session()->forget(self::PROFILE_UNLOCK_SESSION_KEY);

            return false;
        }

        return true;
    }

    private function profileUnlockOtpCacheKey(int $userId, string $siteSlug): string
    {
        return sprintf('customer-profile-unlock-otp:%s:%d', $siteSlug, $userId);
    }

    private function profileUnlockOtpRequestKey(int $userId, string $siteSlug): string
    {
        return sprintf('customer-profile-unlock-otp-request:%s:%d', $siteSlug, $userId);
    }

    private function profileUnlockLinkCacheKey(int $userId, string $siteSlug): string
    {
        return sprintf('customer-profile-unlock-link:%s:%d', $siteSlug, $userId);
    }

    private function profileUnlockLinkRequestKey(int $userId, string $siteSlug): string
    {
        return sprintf('customer-profile-unlock-link-request:%s:%d', $siteSlug, $userId);
    }

    private function resolveSite(Request $request): Site
    {
        /** @var Site $site */
        $site = $request->attributes->get('currentSite');

        return $site;
    }

    private function maskEmail(string $email): string
    {
        if (! str_contains($email, '@')) {
            return mb_substr($email, 0, 3).str_repeat('*', max(mb_strlen($email) - 3, 3));
        }

        [$localPart, $domainPart] = explode('@', $email, 2);
        $visibleLocal = mb_substr($localPart, 0, 3);
        $hiddenLocalLength = max(mb_strlen($localPart) - 3, 3);

        return sprintf('%s%s@%s', $visibleLocal, str_repeat('*', $hiddenLocalLength), $domainPart);
    }

    private function maskPhone(string $phone): string
    {
        if ($phone === '') {
            return '';
        }

        $visiblePrefix = mb_substr($phone, 0, 3);
        $hiddenLength = max(mb_strlen($phone) - 3, 0);

        return $visiblePrefix.str_repeat('*', $hiddenLength);
    }
}
