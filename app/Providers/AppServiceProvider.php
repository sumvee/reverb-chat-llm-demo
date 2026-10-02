<?php

namespace App\Providers;

use App\Services\OllamaClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The in-chat AI assistant's client, built from config/llm.php.
        $this->app->bind(OllamaClient::class, fn () => new OllamaClient(
            config('llm.ollama_url'),
            config('llm.model'),
            (int) config('llm.max_tokens'),
            (int) config('llm.timeout'),
            (float) config('llm.temperature'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
