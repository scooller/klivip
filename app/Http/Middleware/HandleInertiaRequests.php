<?php

namespace App\Http\Middleware;

use App\Models\Site;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        /** @var Site|null $site */
        $site = $request->attributes->get('currentSite');

        return [
            ...parent::share($request),
            'site' => $site ? [
                'name' => $site->name,
                'slug' => $site->slug,
                'address' => $site->address,
                'opening_hours' => $site->opening_hours,
            ] : null,
            'siteSetting' => SiteSetting::forFrontend(),
        ];
    }
}
