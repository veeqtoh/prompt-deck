<?php

declare(strict_types=1);

namespace Veeqtoh\PromptForge\Providers;

use Illuminate\Support\ServiceProvider;
use Veeqtoh\PromptForge\Console\Commands\ActivatePromptCommand;
use Veeqtoh\PromptForge\Console\Commands\ListPromptsCommand;
use Veeqtoh\PromptForge\Console\Commands\MakePromptCommand;
use Veeqtoh\PromptForge\Console\Commands\PromptDiffCommand;
use Veeqtoh\PromptForge\Console\Commands\TestPromptCommand;
use Veeqtoh\PromptForge\PromptManager;

class PromptForgeProvider extends ServiceProvider
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
     * Setup the configuration for PromptForge.
     */
    protected function configure(): void
    {
        // Merge config.
        $this->mergeConfigFrom(
            __DIR__.'/../../config/prompt-forge.php', 'prompt-forge'
        );

        // Register the main manager as a singleton.
        $this->app->singleton(PromptManager::class, function ($app) {
            return new PromptManager(
                config('prompt-forge.path'),
                config('prompt-forge.extension'),
                $app['cache']->store(config('prompt-forge.cache.store')),
                $app['config']
            );
        });

        // Register a facade alias.
        $this->app->alias(PromptManager::class, 'prompt-forge');
    }

    /**
     * Register the package's publishable resources.
     */
    protected function registerPublishing(): void
    {
        // Publish migrations.
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../database/migrations/' => database_path('migrations'),
            ], 'prompt-forge-migrations');

            // Publish config.
            $this->publishes([
                __DIR__.'/../../config/prompt-forge.php' => config_path('prompt-forge.php'),
            ], 'prompt-forge-config');
        }
    }

    /**
     * Register Artisan commands for PromptForge.
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
            // Bind a factory for creating AI-aware agents
            $this->app->singleton(\Veeqtoh\PromptForge\Ai\AgentFactory::class);

            // If you want to automatically decorate Agent classes, you could
            // listen for when an Agent is resolved from the container, but that's advanced.
            // For now, we'll let the factory handle it.
        }
    }
}
