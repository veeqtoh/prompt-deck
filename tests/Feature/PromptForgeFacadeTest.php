<?php

declare(strict_types=1);

use Veeqtoh\PromptForge\Facades\PromptForge;
use Veeqtoh\PromptForge\Prompt;
use Veeqtoh\PromptForge\PromptManager;

test('facade accessor returns prompt-forge', function () {
    // Resolve via facade accessor.
    $resolved = PromptForge::getFacadeRoot();

    expect($resolved)->toBeInstanceOf(PromptManager::class);
});

test('PromptForge::prompt() proxies to PromptManager::prompt()', function () {
    $this->createPromptFixture('facade-test', 1, 'system content', 'user content');

    $prompt = PromptForge::prompt('facade-test', 1);

    expect($prompt)->toBeInstanceOf(Prompt::class)
        ->and($prompt->name())->toBe('facade-test')
        ->and($prompt->version())->toBe(1);
});

test('PromptForge::active() proxies to PromptManager::active()', function () {
    $this->createPromptFixture('facade-active', 1, 'sys', 'usr');
    $this->createPromptFixture('facade-active', 2, 'sys v2', 'usr v2', null, ['active_version' => 2]);

    $prompt = PromptForge::active('facade-active');

    expect($prompt)->toBeInstanceOf(Prompt::class)
        ->and($prompt->version())->toBe(2);
});

test('PromptForge::versions() proxies to PromptManager::versions()', function () {
    $this->createPromptFixture('facade-versions', 1, 'sys', 'usr');
    $this->createPromptFixture('facade-versions', 2, 'sys', 'usr');

    $versions = PromptForge::versions('facade-versions');

    expect($versions)->toHaveCount(2)
        ->and($versions[0]['version'])->toBe(1)
        ->and($versions[1]['version'])->toBe(2);
});

test('PromptForge::activate() proxies to PromptManager::activate()', function () {
    $this->createPromptFixture('facade-activate', 1, 'sys', 'usr');
    $this->createPromptFixture('facade-activate', 2, 'sys', 'usr');

    $result = PromptForge::activate('facade-activate', 2);

    expect($result)->toBeTrue();

    $meta = json_decode(file_get_contents("{$this->tempDir}/facade-activate/metadata.json"), true);
    expect($meta['active_version'])->toBe(2);
});
