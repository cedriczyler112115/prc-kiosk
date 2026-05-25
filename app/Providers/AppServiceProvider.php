<?php

namespace App\Providers;

use Illuminate\Auth\SessionGuard;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
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
        Paginator::useBootstrapFive();

        $guard = Auth::guard();
        if ($guard instanceof SessionGuard) {
            $guard->setRememberDuration(2628000);
        }
    }
}
