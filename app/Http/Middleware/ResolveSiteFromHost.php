<?php

namespace App\Http\Middleware;

use App\Models\Site;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveSiteFromHost
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, callable $next): Response
    {
        $siteSlug = (string) $request->route('site');

        if ($siteSlug === '') {
            abort(404);
        }

        $site = Site::query()
            ->where('slug', $siteSlug)
            ->where('is_active', true)
            ->firstOrFail();

        $request->attributes->set('currentSite', $site);

        return $next($request);
    }
}
