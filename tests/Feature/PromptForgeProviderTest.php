<?php

declare(strict_types=1);

use Veeqtoh\PromptForge\PromptManager;

test('PromptManager is registered as a singleton', function () {
    $instance1 = $this->app->make(PromptManager::class);
    $instance2 = $this->app->make(PromptManager::class);

    expect($instance1)->toBe($instance2);
});

test('PromptManager is resolvable via prompt-forge alias', function () {
    $instance = $this->app->make('prompt-forge');

    expect($instance)->toBeInstanceOf(PromptManager::class);
});

test('alias resolves to same singleton as class binding', function () {
    $byAlias = $this->app->make('prompt-forge');
    $byClass = $this->app->make(PromptManager::class);

    expect($byAlias)->toBe($byClass);
});

test('config is merged from package config file', function () {
    // The provider merges config/prompt-forge.php.
    // Our TestCase overrides some values but the merge should have happened.
    expect(config('prompt-forge.versioning'))->toBe('directory')
        ->and(config('prompt-forge.cache.ttl'))->toBe(3600);
});

test('Artisan commands are registered', function () {
    $commands = \Illuminate\Support\Facades\Artisan::all();

    expect($commands)->toHaveKey('make:prompt')
        ->and($commands)->toHaveKey('prompt:list')
        ->and($commands)->toHaveKey('prompt:activate')
        ->and($commands)->toHaveKey('prompt:diff')
        ->and($commands)->toHaveKey('prompt:test');
});

test('publishable config is registered', function () {
    // Verify the provider has registered publishable resources.
    $publishes = \Illuminate\Support\ServiceProvider::pathsToPublish(
        \Veeqtoh\PromptForge\Providers\PromptForgeProvider::class,
        'prompt-forge-config'
    );

    expect($publishes)->not->toBeEmpty();
});

test('publishable stubs are registered', function () {
    $publishes = \Illuminate\Support\ServiceProvider::pathsToPublish(
        \Veeqtoh\PromptForge\Providers\PromptForgeProvider::class,
        'prompt-forge-stubs'
    );

    expect($publishes)->not->toBeEmpty()
        ->and(array_values($publishes))->each->toContain('stubs/prompt-forge/');
});
