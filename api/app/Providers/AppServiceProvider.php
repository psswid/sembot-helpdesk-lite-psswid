<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Triage\TriageClient;
use App\Services\Triage\OpenAiTriageClient;
use App\Services\Triage\MockTriageClient;
use App\Services\Triage\TriageService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind triage client implementation based on configured driver.
        $this->app->singleton(TriageClient::class, function ($app) {
            $driver = config('services.llm.driver', 'mock');
            return match ($driver) {
                'openai' => new OpenAiTriageClient(),
                default => new MockTriageClient(),
            };
        });

        // Triage service depends on the client – expose it for DI.
        $this->app->singleton(TriageService::class, function ($app) {
            return new TriageService($app->make(TriageClient::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
