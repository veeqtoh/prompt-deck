<?php

declare(strict_types=1);

namespace Veeqtoh\PromptDeck\Providers;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Veeqtoh\PromptDeck\Console\Commands\ActivatePromptCommand;
use Veeqtoh\PromptDeck\Console\Commands\ListPromptsCommand;
use Veeqtoh\PromptDeck\Console\Commands\MakePromptCommand;
use Veeqtoh\PromptDeck\Console\Commands\PromptDiffCommand;
use Veeqtoh\PromptDeck\Console\Commands\TestPromptCommand;
use Veeqtoh\PromptDeck\PromptManager;

class PromptDeckServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerArtisanCommands();
        $this->registerAiSdkIntegration();
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->configure();
    }

    /**
     * Setup the configuration for PromptDeck.
     */
    protected function configure(): void
    {
        // Merge config.
        $this->mergeConfigFrom(
            __DIR__.'/../../config/prompt-deck.php', 'prompt-deck'
        );

        // Register the main manager as a singleton.
        $this->app->singleton(PromptManager::class, function ($app) {
            return new PromptManager(
                config('prompt-deck.path'),
                config('prompt-deck.extension'),
                $app['cache']->store(config('prompt-deck.cache.store')),
                $app['config']
            );
        });

        // Register a facade alias.
        $this->app->alias(PromptManager::class, 'prompt-deck');
    }

    /**
     * Register the package's publishable resources.
     */
    protected function registerPublishing(): void
    {
        // Publish migrations.
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../database/migrations/' => database_path('migrations'),
            ], 'prompt-deck-migrations');

            // Publish config.
            $this->publishes([
                __DIR__.'/../../config/prompt-deck.php' => config_path('prompt-deck.php'),
            ], 'prompt-deck-config');
        }
    }

    /**
     * Register Artisan commands for PromptDeck.
     */
    protected function registerArtisanCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakePromptCommand::class,
                ListPromptsCommand::class,
                ActivatePromptCommand::class,
                PromptDiffCommand::class,
                TestPromptCommand::class,
            ]);
        }
    }

    /**
     * Register bindings for Laravel AI SDK integration.
     */
    protected function registerAiSdkIntegration(): void
    {
        if (class_exists(\Laravel\Ai\AiServiceProvider::class)) {
            $this->app->singleton(\Veeqtoh\PromptDeck\Ai\TrackPromptMiddleware::class);

            // Auto-scaffold a prompt when `make:agent` finishes successfully.
            if (config('prompt-deck.scaffold_on_make_agent', true)) {
                Event::listen(
                    CommandFinished::class,
                    \Veeqtoh\PromptDeck\Listeners\AfterMakeAgent::class
                );
            }
        }
    }
}
