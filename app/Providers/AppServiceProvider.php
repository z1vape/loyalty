<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

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
        if ((config('app.env') === 'production' || str_starts_with(config('app.url'), 'https://')) && !app()->runningInConsole()) {
            $host = request()->getHost() ?? '';
            if (!in_array($host, ['127.0.0.1', 'localhost'])) {
                URL::forceScheme('https');
            }
        }

        // Implicitly grant "Super Admin" role all permissions and dynamically check role permissions
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('Super Admin')) {
                return true;
            }

            if ($user->hasPermission($ability)) {
                return true;
            }

            return null;
        });
    }
}
