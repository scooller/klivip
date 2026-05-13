<?php

namespace App\Http\Controllers\Front;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        /** @var Site $site */
        $site = $request->attributes->get('currentSite');
        $customer = Auth::guard('customer')->user();
        $configuredAdminDomain = (string) config('app.admin_domain');
        $normalizedAdminDomain = parse_url($configuredAdminDomain, PHP_URL_HOST) ?: $configuredAdminDomain;
        $adminPortal = [
            'url' => sprintf('%s://%s', $request->getScheme(), $normalizedAdminDomain),
        ];

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
                    'role' => UserRole::User->value,
                ] : null,
                'adminPortal' => $adminPortal,
            ],
            'benefits' => [
                'Bonos y promociones personalizadas',
                'Acceso rapido a juegos destacados',
                'Soporte prioritario para tu cuenta',
            ],
        ]);
    }
}
