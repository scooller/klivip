<?php

use App\Http\Controllers\Front\CustomerSessionController;
use App\Http\Controllers\Front\HomeController;
use App\Http\Controllers\Front\UserController;
use App\Http\Middleware\ResolveSiteFromHost;
use Illuminate\Support\Facades\Route;

$publicBaseDomains = [
    'klivip.test',
    'klivip.cloud',
];

foreach ($publicBaseDomains as $baseDomain) {
    $routeNamePrefix = $baseDomain === 'klivip.test'
        ? 'front.user'
        : 'front.user.' . str_replace('.', '_', $baseDomain);

    Route::domain('{site}.' . $baseDomain)
        ->where(['site' => '[A-Za-z0-9-]+'])
        ->middleware([ResolveSiteFromHost::class])
        ->group(function () use ($routeNamePrefix): void {
            Route::get('/', HomeController::class);
            Route::get('/usuario', UserController::class)->name($routeNamePrefix);
            Route::post('/usuario/login', [CustomerSessionController::class, 'store'])
                ->middleware('guest:customer')
                ->name($routeNamePrefix . '.login');
            Route::post('/usuario/logout', [CustomerSessionController::class, 'destroy'])
                ->middleware('auth:customer')
                ->name($routeNamePrefix . '.logout');
        });
}

Route::get('/', function () {
    return 'Klivip app is running. Use admin.klivip.test or {site}.klivip.test';
})->name('root');
