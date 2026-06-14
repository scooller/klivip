<?php

namespace Database\Seeders;

use App\Enums\BannerScope;
use App\Models\Banner;
use App\Models\Site;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class BannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $siteOne = Site::query()->firstWhere('slug', 'sitio1');
        $siteTwo = Site::query()->firstWhere('slug', 'sitio2');

        if (! $siteOne || ! $siteTwo) {
            return;
        }

        $globalImagePath = $this->ensureDemoBannerStored('banner-1-muestra.png');
        $siteOneImagePath = $this->ensureDemoBannerStored('banner-2-muestra.png');
        $multiSiteImagePath = $this->ensureDemoBannerStored('banner-1-boton-on.png');

        $globalBanner = Banner::query()->updateOrCreate(
            ['title' => 'Banner Global Bienvenida'],
            [
                'image_path' => $globalImagePath,
                'target_url' => null,
                'scope' => BannerScope::Global,
                'section' => 'home',
                'sort_order' => 1,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addMonth(),
                'is_active' => true,
            ],
        );

        $siteOnlyBanner = Banner::query()->updateOrCreate(
            ['title' => 'Banner Sitio 1 Exclusivo'],
            [
                'image_path' => $siteOneImagePath,
                'target_url' => null,
                'scope' => BannerScope::Sites,
                'section' => 'home',
                'sort_order' => 2,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addWeeks(3),
                'is_active' => true,
            ],
        );

        $multiSiteBanner = Banner::query()->updateOrCreate(
            ['title' => 'Banner Campana Multi Sitio'],
            [
                'image_path' => $multiSiteImagePath,
                'target_url' => null,
                'scope' => BannerScope::Sites,
                'section' => 'home',
                'sort_order' => 3,
                'starts_at' => now()->subHours(12),
                'ends_at' => now()->addWeeks(2),
                'is_active' => true,
            ],
        );

        $globalBanner->sites()->sync([]);
        $siteOnlyBanner->sites()->sync([$siteOne->id]);
        $multiSiteBanner->sites()->sync([$siteOne->id, $siteTwo->id]);
    }

    private function ensureDemoBannerStored(string $fileName): string
    {
        $sourcePath = public_path('images/banners/' . $fileName);
        $destinationPath = 'banners/' . $fileName;

        if (is_file($sourcePath) && ! Storage::disk('public')->exists($destinationPath)) {
            Storage::disk('public')->put($destinationPath, file_get_contents($sourcePath));
        }

        return $destinationPath;
    }
}
