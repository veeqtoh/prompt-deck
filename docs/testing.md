# Testing

- [Introduction](#introduction)
- [Setting Up Tests](#setting-up-tests)
    - [Test Base Class](#test-base-class)
    - [Disabling Cache and Tracking](#disabling-cache-and-tracking)
- [Testing Prompts](#testing-prompts)
    - [Creating Test Prompt Files](#creating-test-prompt-files)
    - [Testing Prompt Loading](#testing-prompt-loading)
    - [Testing Variable Interpolation](#testing-variable-interpolation)
    - [Testing Roles](#testing-roles)
    - [Testing Messages Output](#testing-messages-output)
- [Testing with the Facade](#testing-with-the-facade)
- [Testing Version Management](#testing-version-management)
- [Testing Execution Tracking](#testing-execution-tracking)
    - [Using Factories](#using-factories)
- [Testing Artisan Commands](#testing-artisan-commands)
- [Testing AI SDK Integration](#testing-ai-sdk-integration)
    - [Testing HasPromptTemplate](#testing-hasprompttemplate)
    - [Clearing Cached Templates](#clearing-cached-templates)

<a name="introduction"></a>
## Introduction

Prompt Deck is designed to be easily testable. This guide covers strategies for testing prompts, commands, tracking, and AI SDK integration in your Laravel application.

<a name="setting-up-tests"></a>
## Setting Up Tests

<a name="test-base-class"></a>
### Test Base Class

If you're using Orchestra Testbench (recommended for package testing), register the service provider in your test case:

```php
<?php

namespace Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Veeqtoh\PromptDeck\Providers\PromptDeckServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            PromptDeckServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'PromptDeck' => \Veeqtoh\PromptDeck\Facades\PromptDeck::class,
        ];
    }
}
```

<a name="disabling-cache-and-tracking"></a>
### Disabling Cache and Tracking

For most tests, disable caching and tracking to avoid side effects:

```php
protected function defineEnvironment($app): void
{
    $app['config']->set('prompt-deck.cache.enabled', false);
    $app['config']->set('prompt-deck.tracking.enabled', false);
    $app['config']->set('prompt-deck.path', $this->getFixturePath('prompts'));
}

protected function getFixturePath(string $path = ''): string
{
    return __DIR__ . '/fixtures/' . $path;
}
```

<a name="testing-prompts"></a>
## Testing Prompts

<a name="creating-test-prompt-files"></a>
### Creating Test Prompt Files

Create fixture prompt files in your test directory:

```
tests/
└── fixtures/
    └── prompts/
        └── order-summary/
            ├── v1/
            │   ├── system.md
            │   ├── user.md
            │   └── metadata.json
            └── metadata.json
```

`tests/fixtures/prompts/order-summary/v1/system.md`:
```markdown
You are a {{ $tone }} AI assistant specialized in order summaries.
```

`tests/fixtures/prompts/order-summary/v1/user.md`:
```markdown
Summarise this order: {{ $input }}
```

<a name="testing-prompt-loading"></a>
### Testing Prompt Loading

```php
use Veeqtoh\PromptDeck\Facades\PromptDeck;

it('loads a prompt by name', function () {
    $prompt = PromptDeck::get('order-summary', 1);

    expect($prompt->name())->toBe('order-summary');
    expect($prompt->version())->toBe(1);
});

it('throws when prompt does not exist', function () {
    PromptDeck::get('non-existent');
})->throws(\Veeqtoh\PromptDeck\Exceptions\PromptNotFoundException::class);

it('throws when version does not exist', function () {
    PromptDeck::get('order-summary', 999);
})->throws(\Veeqtoh\PromptDeck\Exceptions\InvalidVersionException::class);
```

<a name="testing-variable-interpolation"></a>
### Testing Variable Interpolation

```php
it('interpolates variables in prompt content', function () {
    $prompt = PromptDeck::get('order-summary', 1);

    $content = $prompt->system(['tone' => 'friendly']);

    expect($content)->toContain('friendly');
    expect($content)->not->toContain('{{ $tone }}');
});

it('leaves unmatched placeholders intact', function () {
    $prompt = PromptDeck::get('order-summary', 1);

    $content = $prompt->system([]);

    expect($content)->toContain('{{ $tone }}');
});
```

<a name="testing-roles"></a>
### Testing Roles

```php
it('lists available roles', function () {
    $prompt = PromptDeck::get('order-summary', 1);

    expect($prompt->roles())->toContain('system', 'user');
});

it('checks if a role exists', function () {
    $prompt = PromptDeck::get('order-summary', 1);

    expect($prompt->has('system'))->toBeTrue();
    expect($prompt->has('nonexistent'))->toBeFalse();
});

it('returns empty string for missing roles', function () {
    $prompt = PromptDeck::get('order-summary', 1);

    expect($prompt->role('nonexistent'))->toBe('');
});

it('gets raw content without interpolation', function () {
    $prompt = PromptDeck::get('order-summary', 1);

    $raw = $prompt->raw('system');

    expect($raw)->toContain('{{ $tone }}');
});
```

<a name="testing-messages-output"></a>
### Testing Messages Output

```php
it('builds messages array for AI APIs', function () {
    $prompt = PromptDeck::get('order-summary', 1);

    $messages = $prompt->toMessages(['tone' => 'friendly', 'input' => 'Order #123']);

    expect($messages)->toBeArray();
    expect($messages[0])->toHaveKeys(['role', 'content']);
    expect($messages[0]['role'])->toBe('system');
});

it('filters messages to specific roles', function () {
    $prompt = PromptDeck::get('order-summary', 1);

    $messages = $prompt->toMessages([], ['system']);

    expect($messages)->toHaveCount(1);
    expect($messages[0]['role'])->toBe('system');
});
```

<a name="testing-with-the-facade"></a>
## Testing with the Facade

You can mock the facade in tests where you don't want filesystem access:

```php
use Veeqtoh\PromptDeck\Facades\PromptDeck;
use Veeqtoh\PromptDeck\PromptTemplate;

it('uses a mocked prompt', function () {
    PromptDeck::shouldReceive('get')
        ->with('order-summary', null)
        ->andReturn(new PromptTemplate(
            'order-summary',
            1,
            ['system' => 'You are a helpful assistant.'],
            ['description' => 'Test prompt']
        ));

    $prompt = PromptDeck::get('order-summary');

    expect($prompt->system())->toBe('You are a helpful assistant.');
});
```

<a name="testing-version-management"></a>
## Testing Version Management

```php
it('lists all versions for a prompt', function () {
    $versions = PromptDeck::versions('order-summary');

    expect($versions)->toBeArray();
    expect($versions[0])->toHaveKey('version');
});

it('activates a specific version', function () {
    // With tracking disabled, this writes to metadata.json
    $result = PromptDeck::activate('order-summary', 1);

    expect($result)->toBeTrue();
});
```

<a name="testing-execution-tracking"></a>
## Testing Execution Tracking

When testing tracking, enable it and run the migrations in your test setup:

```php
protected function defineEnvironment($app): void
{
    $app['config']->set('prompt-deck.tracking.enabled', true);
    $app['config']->set('database.default', 'testing');
}

protected function defineDatabaseMigrations(): void
{
    $this->loadMigrationsFrom(__DIR__ . '/../src/database/migrations');
}
```

```php
use Veeqtoh\PromptDeck\Models\PromptExecution;

it('tracks prompt executions', function () {
    PromptDeck::track('order-summary', 1, [
        'input'  => ['message' => 'test'],
        'output' => 'response',
        'tokens' => 100,
    ]);

    expect(PromptExecution::count())->toBe(1);
    expect(PromptExecution::first()->prompt_name)->toBe('order-summary');
    expect(PromptExecution::first()->tokens)->toBe(100);
});

it('does not track when disabled', function () {
    config(['prompt-deck.tracking.enabled' => false]);

    PromptDeck::track('order-summary', 1, ['tokens' => 50]);

    expect(PromptExecution::count())->toBe(0);
});
```

<a name="using-factories"></a>
### Using Factories

Use the included factories to seed test data:

```php
use Veeqtoh\PromptDeck\Models\PromptVersion;
use Veeqtoh\PromptDeck\Models\PromptExecution;

it('queries execution data', function () {
    PromptExecution::factory()
        ->forPrompt('order-summary', 2)
        ->count(10)
        ->create();

    $avg = PromptExecution::where('prompt_name', 'order-summary')
        ->avg('latency_ms');

    expect($avg)->toBeGreaterThan(0);
});

it('finds the active version', function () {
    PromptVersion::factory()->named('order-summary')->version(1)->create();
    PromptVersion::factory()->named('order-summary')->version(2)->active()->create();

    $active = PromptVersion::where('name', 'order-summary')
        ->where('is_active', true)
        ->first();

    expect($active->version)->toBe(2);
});
```

<a name="testing-artisan-commands"></a>
## Testing Artisan Commands

```php
use Illuminate\Support\Facades\File;

it('creates a prompt via make:prompt', function () {
    $this->artisan('make:prompt', ['name' => 'test-prompt'])
        ->assertSuccessful();

    $promptPath = config('prompt-deck.path') . '/test-prompt/v1';
    expect(File::isDirectory($promptPath))->toBeTrue();
    expect(File::exists($promptPath . '/system.md'))->toBeTrue();
});

it('lists prompts via prompt:list', function () {
    // Create a prompt first
    $this->artisan('make:prompt', ['name' => 'list-test']);

    $this->artisan('prompt:list')
        ->assertSuccessful();
});

it('activates a version via prompt:activate', function () {
    $this->artisan('make:prompt', ['name' => 'activate-test']);

    $this->artisan('prompt:activate', [
        'name'    => 'activate-test',
        'version' => 1,
    ])->assertSuccessful();
});

it('tests a prompt via prompt:test', function () {
    $this->artisan('make:prompt', ['name' => 'render-test']);

    $this->artisan('prompt:test', [
        'name' => 'render-test',
    ])->assertSuccessful();
});

it('shows diff between versions', function () {
    $this->artisan('make:prompt', ['name' => 'diff-test']);

    // Create v2 by answering the interactive prompt
    // Or set up fixtures with two versions

    $this->artisan('prompt:diff', [
        'name'  => 'diff-test',
        '--v1'  => 1,
        '--v2'  => 1,  // Compare with self for basic test
    ])->assertSuccessful();
});
```

<a name="testing-ai-sdk-integration"></a>
## Testing AI SDK Integration

<a name="testing-hasprompttemplate"></a>
### Testing HasPromptTemplate

Create a test agent class that uses the trait:

```php
use Veeqtoh\PromptDeck\Concerns\HasPromptTemplate;

class TestAgent
{
    use HasPromptTemplate;
}
```

```php
it('derives prompt name from class name', function () {
    $agent = new TestAgent;

    expect($agent->promptName())->toBe('test-agent');
});

it('returns null for default prompt version', function () {
    $agent = new TestAgent;

    expect($agent->promptVersion())->toBeNull();
});

it('returns empty array for default variables', function () {
    $agent = new TestAgent;

    expect($agent->promptVariables())->toBe([]);
});
```

<a name="clearing-cached-templates"></a>
### Clearing Cached Templates

When testing with different prompt versions:

```php
it('clears cached template between tests', function () {
    $agent = new TestAgent;

    // Load v1
    $template1 = $agent->promptTemplate();

    // Clear cache and load fresh
    $agent->forgetPromptTemplate();
    $template2 = $agent->promptTemplate();

    // Both are fresh instances
    expect($template1)->not->toBe($template2);
});
```

> **Tip**
> Always call `forgetPromptTemplate()` in tests where you modify prompt files or change the active version between assertions.
