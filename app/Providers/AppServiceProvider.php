<?php

namespace App\Providers;

use App\Models\StoreSetting;
use Illuminate\Support\Facades\View;
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
        if (app()->runningInConsole() || app()->runningUnitTests()) {
            View::share('storeSetting', new StoreSetting([
                'store_name' => 'CV. Tri Jaya',
            ]));

            return;
        }

        View::share('storeSetting', StoreSetting::query()->firstOrCreate([], [
            'store_name' => 'CV. Tri Jaya',
        ]));
    }
}
