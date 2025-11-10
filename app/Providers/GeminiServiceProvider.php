<?php

namespace App\Providers;

use App\Services\Ai\ImagenClient;
use App\Services\GeminiService;
use Illuminate\Support\ServiceProvider;

class GeminiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GeminiService::class, function ($app) {
            return new GeminiService();
        });

        $this->app->singleton(ImagenClient::class, function ($app) {
            return new ImagenClient();
        });
    }

    public function boot(): void
    {
        //
    }
}
