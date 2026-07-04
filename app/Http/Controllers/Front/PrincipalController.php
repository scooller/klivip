<?php

namespace App\Http\Controllers\Front;

use App\Enums\BannerScope;
use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PrincipalController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        /** @var Site $site */
        $site = $request->attributes->get('currentSite');
        $customer = Auth::guard('customer')->user();

        $banners = $this->resolveActiveBanners($site);

        return Inertia::render('Principal', [
            'site' => [
                'name' => $site->name,
                'slug' => $site->slug,
                'logo' => $site->logo ? asset('storage/'.$site->logo) : null,
                'content' => $site->content,
                'address' => $site->address,
                'opening_hours' => $site->opening_hours,
                'links' => $site->links,
                'url' => $site->url,
            ],
            'banners' => $banners,
            'auth' => [
                'customer' => $customer ? [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                ] : null,
            ],
        ]);
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function resolveActiveBanners(Site $site): array
    {
        return Banner::query()
            ->where('is_active', true)
            ->where(function ($query) use ($site): void {
                $query
                    ->where('scope', BannerScope::Global->value)
                    ->orWhereHas('sites', fn ($siteQuery) => $siteQuery->where('sites.id', $site->id));
            })
            ->where(function ($query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(fn (Banner $banner): array => [
                'id' => (string) $banner->id,
                'title' => $banner->title,
                'section' => $banner->section,
                'image_url' => $this->resolveBannerImageUrl((string) $banner->image_path),
                'target_url' => $banner->target_url,
            ])
            ->values()
            ->all();
    }

    private function resolveBannerImageUrl(string $path): string
    {
        $publicStoragePath = public_path('storage/'.$path);

        if (is_file($publicStoragePath)) {
            return asset('storage/'.$path);
        }

        $fallbackName = basename($path);
        $fallbackPublicPath = public_path('images/banners/'.$fallbackName);

        if (is_file($fallbackPublicPath)) {
            return asset('images/banners/'.$fallbackName);
        }

        return asset('storage/'.$path);
    }
}
