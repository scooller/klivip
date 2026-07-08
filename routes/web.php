<?php

use App\Http\Controllers\Front\CustomerSessionController;
use App\Http\Controllers\Front\PrincipalController;
use App\Http\Controllers\Front\ScheduleController;
use App\Http\Controllers\Front\UserController;
use App\Http\Controllers\Front\UserSweepstakeCouponsController;
use App\Http\Controllers\RedemptionController;
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
            Route::get('/', PrincipalController::class)
                ->middleware('auth:customer')
                ->name($routeNamePrefix . '.principal');
            Route::get('/cuenta', UserController::class)
                ->name($routeNamePrefix . '.account');
            Route::get('/programacion', ScheduleController::class)
                ->middleware('auth:customer')
                ->name($routeNamePrefix . '.schedule');
            Route::get('/usuario', UserController::class)->name($routeNamePrefix);
            Route::get('/usuario/cupones', [UserSweepstakeCouponsController::class, 'index'])
                ->middleware('auth:customer')
                ->name($routeNamePrefix . '.coupons.index');
            Route::post('/usuario/cupones/redeem', [UserSweepstakeCouponsController::class, 'redeemByCode'])
                ->middleware('auth:customer')
                ->name($routeNamePrefix . '.coupons.redeem');
            Route::post('/usuario/perfil', [UserController::class, 'updateProfile'])
                ->middleware('auth:customer')
                ->name($routeNamePrefix . '.profile.update');
            Route::delete('/usuario/perfil', [UserController::class, 'destroyProfile'])
                ->middleware('auth:customer')
                ->name($routeNamePrefix . '.profile.destroy');
            Route::post('/usuario/perfil/unlock/otp/request', [UserController::class, 'requestProfileUnlockOtp'])
                ->middleware('auth:customer')
                ->name($routeNamePrefix . '.profile.unlock.otp.request');
            Route::post('/usuario/perfil/unlock/otp/verify', [UserController::class, 'verifyProfileUnlockOtp'])
                ->middleware('auth:customer')
                ->name($routeNamePrefix . '.profile.unlock.otp.verify');
            Route::post('/usuario/perfil/unlock/link/request', [UserController::class, 'requestProfileUnlockLink'])
                ->middleware('auth:customer')
                ->name($routeNamePrefix . '.profile.unlock.link.request');
            Route::get('/usuario/perfil/unlock/link/{token}', [UserController::class, 'consumeProfileUnlockLink'])
                ->middleware(['auth:customer', 'signed'])
                ->name($routeNamePrefix . '.profile.unlock.link');
            Route::post('/usuario/login', [CustomerSessionController::class, 'store'])
                ->middleware('guest:customer')
                ->name($routeNamePrefix . '.login');
            Route::post('/usuario/register', [CustomerSessionController::class, 'register'])
                ->middleware('guest:customer')
                ->name($routeNamePrefix . '.register');
            Route::post('/usuario/register/verify', [CustomerSessionController::class, 'verifyRegistration'])
                ->middleware('guest:customer')
                ->name($routeNamePrefix . '.register.verify');
            Route::post('/usuario/login/verify', [CustomerSessionController::class, 'verify'])
                ->middleware('guest:customer')
                ->name($routeNamePrefix . '.login.verify');
            Route::post('/usuario/logout', [CustomerSessionController::class, 'destroy'])
                ->middleware('auth:customer')
                ->name($routeNamePrefix . '.logout');
            Route::get('/redemption/{code}', [RedemptionController::class, 'show'])
                ->middleware('auth:customer')
                ->where('code', '[A-Za-z0-9_-]+')
                ->name($routeNamePrefix . '.redemption.show');
            Route::post('/redemption/{code}/redeem', [RedemptionController::class, 'redeem'])
                ->middleware('auth:customer')
                ->where('code', '[A-Za-z0-9_-]+')
                ->name($routeNamePrefix . '.redemption.redeem');
        });
}

Route::get('/', function () {
    return 'Klivip app is running. Use admin.klivip.test or {site}.klivip.test';
})->name('root');
