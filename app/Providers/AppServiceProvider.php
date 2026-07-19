<?php

namespace App\Providers;

use App\Listeners\RewardEventSubscriber;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::subscribe(RewardEventSubscriber::class);

        // Track last login time for all auth guards (customer + admin)
        Event::listen(
            Login::class,
            function (Login $event): void {
                if (method_exists($event->user, 'forceFill')) {
                    $event->user->forceFill(['last_login_at' => now()])->save();
                }
            }
        );
    }
}
