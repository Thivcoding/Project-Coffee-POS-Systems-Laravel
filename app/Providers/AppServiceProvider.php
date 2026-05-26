<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
        //
        if (app()->environment('production') || env('VERCEL')) {
            URL::forceScheme('https');

            @mkdir('/tmp/storage/framework/views', 0777, true);
            @mkdir('/tmp/storage/framework/cache', 0777, true);
        }
    }
}
