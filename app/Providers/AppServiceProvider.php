<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Foundation\Vite;

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
        if (env('APP_ENV') !== 'local') {
            URL::forceScheme('https');
        }

        // Instantiate Vite and use the non-static method
        app(Vite::class)->useBuildDirectory('build');
    }
}
