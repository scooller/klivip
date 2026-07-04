<?php

namespace App\Http\Controllers\Front;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\SiteSetting;
use App\Models\User;
use App\Notifications\FrontCustomerOtpNotification;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class CustomerSessionController extends Controller
{
    public function register(Request $request): RedirectResponse
    {
        $adultLimitDate = CarbonImmutable::now()->subYears(18)->toDateString();

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc,dns', 'max:255', 'confirmed', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:25'],
            'birth_date' => ['required', 'date', 'before_or_equal:' . $adultLimitDate],
        ], [
            'email.confirmed' => 'El correo y la confirmacion de correo deben coincidir.',
            'birth_date.before_or_equal' => 'Debes ser mayor de 18 anos para registrarte.',
        ]);

        $normalizedPhone = $this->normalizePhone((string) $payload['phone']);

        if ($normalizedPhone === null) {
            return back()->withErrors([
                'phone' => 'Ingresa un numero de telefono valido.',
            ]);
        }

        $phoneExists = User::query()
            ->where('phone', $normalizedPhone)
            ->exists();

        if ($phoneExists) {
            return back()->withErrors([
                'phone' => 'Este numero de telefono ya esta registrado.',
            ]);
        }

        $site = $this->resolveSite($request);

        $customer = User::create([
            'name' => trim((string) $payload['name']),
            'email' => mb_strtolower(trim((string) $payload['email'])),
            'phone' => $normalizedPhone,
            'birth_date' => $payload['birth_date'],
            'password' => Hash::make(Str::random(40)),
            'role' => UserRole::User,
        ]);

        $customer->sites()->syncWithoutDetaching([$site->id]);

        return back()->with('customer_registration_status', 'created');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $identifier = (string) $credentials['identifier'];
        $customer = $this->resolveCustomer($identifier);

        if ($customer === null) {
            return back()->withErrors([
                'identifier' => 'No encontramos una cuenta con ese numero de telefono o correo.',
            ]);
        }

        if ((bool) SiteSetting::get('enable_home_login_without_code', false)) {
            $this->clearOtpSession($request);

            Auth::guard('customer')->login($customer, $request->boolean('remember'));
            $request->session()->regenerate();

            return redirect()->intended('/');
        }

        $site = $this->resolveSite($request);
        $requestKey = $this->otpRequestKey($site->slug, $customer->email);

        if (RateLimiter::tooManyAttempts($requestKey, 1)) {
            return back()->withErrors([
                'identifier' => 'Espera un momento antes de solicitar un nuevo codigo.',
            ]);
        }

        RateLimiter::hit($requestKey, 60);

        $otpCode = Str::upper(Str::random(6));
        $cacheKey = $this->otpCacheKey($site->slug, $customer->email);

        Cache::put($cacheKey, [
            'user_id' => $customer->getKey(),
            'code_hash' => Hash::make($otpCode),
        ], now()->addMinutes(10));

        $request->session()->put([
            'customer_otp_identifier' => $identifier,
            'customer_otp_email' => $customer->email,
            'customer_otp_expires_at' => now()->addMinutes(10)->timestamp,
            'customer_otp_remember' => $request->boolean('remember'),
        ]);

        try {
            $customer->notify(new FrontCustomerOtpNotification($otpCode, $site->name));
        } catch (TransportExceptionInterface $exception) {
            Cache::forget($cacheKey);
            RateLimiter::clear($requestKey);

            return back()->withErrors([
                'identifier' => 'No se pudo enviar el codigo de acceso. Revisa la configuracion de correo e intenta nuevamente.',
            ]);
        }

        $request->session()->flash('customer_otp_status', 'sent');

        return back();
    }

    public function verify(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'otp_code' => ['required', 'alpha_num:6'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $identifier = (string) $credentials['identifier'];
        $customer = $this->resolveCustomer($identifier);

        if ($customer === null) {
            return back()->withErrors([
                'identifier' => 'No encontramos una cuenta con ese numero de telefono o correo.',
            ]);
        }

        $site = $this->resolveSite($request);
        $cacheKey = $this->otpCacheKey($site->slug, $customer->email);
        $otpPayload = Cache::get($cacheKey);

        if (! is_array($otpPayload) || ! isset($otpPayload['code_hash']) || ! Hash::check((string) $credentials['otp_code'], (string) $otpPayload['code_hash'])) {
            return back()->withErrors([
                'otp_code' => 'El codigo no es valido o ya expiro.',
            ]);
        }

        Cache::forget($cacheKey);
        RateLimiter::clear($this->otpRequestKey($site->slug, $customer->email));
        $this->clearOtpSession($request);

        Auth::guard('customer')->login($customer, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended('/');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return back();
    }

    private function resolveCustomer(string $identifier): ?User
    {
        $normalizedEmail = mb_strtolower(trim($identifier));
        $normalizedPhone = $this->normalizePhone($identifier);

        $customer = User::query()
            ->where(function ($query) use ($normalizedEmail, $normalizedPhone): void {
                $query->whereRaw('LOWER(email) = ?', [$normalizedEmail]);

                if ($normalizedPhone !== null) {
                    $query->orWhere('phone', $normalizedPhone);
                }
            })
            ->where('role', UserRole::User)
            ->first();

        return $customer;
    }

    private function normalizePhone(string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if (Str::length($digits) < 8) {
            return null;
        }

        return '+' . $digits;
    }

    private function resolveSite(Request $request): Site
    {
        /** @var Site $site */
        $site = $request->attributes->get('currentSite');

        return $site;
    }

    private function otpCacheKey(string $siteSlug, string $email): string
    {
        return sprintf('customer-otp:%s:%s', $siteSlug, sha1(mb_strtolower($email)));
    }

    private function otpRequestKey(string $siteSlug, string $email): string
    {
        return sprintf('customer-otp-request:%s:%s', $siteSlug, sha1(mb_strtolower($email)));
    }

    private function clearOtpSession(Request $request): void
    {
        $request->session()->forget([
            'customer_otp_identifier',
            'customer_otp_email',
            'customer_otp_expires_at',
            'customer_otp_remember',
        ]);
    }
}
