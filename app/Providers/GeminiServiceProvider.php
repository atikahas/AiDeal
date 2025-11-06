<?php

namespace App\Providers;

use App\Services\GeminiService;
use Illuminate\Support\ServiceProvider;

class GeminiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GeminiService::class, function ($app) {
            return new GeminiService();
        });
    }

    public function boot(): void
    {
        //
    }
}
