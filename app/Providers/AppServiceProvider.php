<?php

namespace App\Providers;

use App\Contracts\AiVerifierContract;
use App\Contracts\YouTubeClientContract;
use App\Services\AiVerifier;
use App\Services\YouTubeClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(YouTubeClientContract::class, YouTubeClient::class);
        $this->app->bind(AiVerifierContract::class, AiVerifier::class);
    }
}
