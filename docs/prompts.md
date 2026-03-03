# Prompts

- [Introduction](#introduction)
- [Retrieving Prompts](#retrieving-prompts)
    - [The PROMPTDECK Facade](#the-prompt-facade)
    - [Active Version](#active-version)
    - [Specific Version](#specific-version)
- [Rendering Roles](#rendering-roles)
    - [Dynamic Role Methods](#dynamic-role-methods)
    - [The role Method](#the-role-method)
    - [Raw Content](#raw-content)
- [Inspecting Prompts](#inspecting-prompts)
    - [Available Roles](#available-roles)
    - [Checking for a Role](#checking-for-a-role)
    - [Metadata](#metadata)
- [Building Messages for AI APIs](#building-messages-for-ai-apis)
    - [All Roles](#all-roles)
    - [Filtering Roles](#filtering-roles)
- [Variable Interpolation](#variable-interpolation)
    - [Syntax](#syntax)
    - [Supported Value Types](#supported-value-types)
    - [Missing Variables](#missing-variables)
- [Versioning](#versioning)
    - [Listing Versions](#listing-versions)
    - [Activating a Version](#activating-a-version)
    - [Version Resolution](#version-resolution)
- [Caching](#caching)
- [Execution Tracking](#execution-tracking)
- [Serialisation](#serialisation)
- [Configuration](#configuration)

<a name="introduction"></a>
## Introduction

Prompt Deck provides a clean, expressive API for loading, rendering, and managing versioned AI prompts in your Laravel application. Prompts are stored as plain files on disk — organised by name, version, and role — and are accessed through the `PROMPTDECK` facade, which feels like any other first-party Laravel service.

A prompt can contain multiple **roles** (system, user, assistant, developer, tool, or any custom role you define). Each role's content supports `{{ $variable }}` interpolation, and the entire prompt can be converted into a messages array ready to send to OpenAI, Anthropic, or any chat-completion API.

```php
use Veeqtoh\PromptDeck\Facades\PROMPTDECK;

$prompt = PROMPTDECK::get('order-summary');

$prompt->system(['tone' => 'friendly']);
// "You are a friendly AI assistant..."

$prompt->user(['input' => $request->message]);
// "Summarise the following order: ..."
```

<a name="retrieving-prompts"></a>
## Retrieving Prompts

<a name="the-prompt-facade"></a>
### The PROMPTDECK Facade

The `PROMPTDECK` facade is the primary entry point for loading prompts. It delegates to the `PromptManager` singleton registered by the service provider:

```php
use Veeqtoh\PromptDeck\Facades\PROMPTDECK;

// Load the active version
$prompt = PROMPTDECK::get('order-summary');

// Load a specific version
$prompt = PROMPTDECK::get('order-summary', 2);
```

The `get` method returns a `Veeqtoh\PromptDeck\PromptTemplate` instance. If no version is specified, the [active version](#version-resolution) is resolved automatically.

<a name="active-version"></a>
### Active Version

You can also use the `active` method to be explicit about loading the active version:

```php
$prompt = PROMPTDECK::active('order-summary');

$prompt->version(); // e.g. 3
```

<a name="specific-version"></a>
### Specific Version

Pass a version number as the second argument to `get` to load a specific version, regardless of which version is currently active:

```php
$prompt = PROMPTDECK::get('order-summary', 1);

$prompt->version(); // 1
```

If the version does not exist, an `InvalidVersionException` is thrown.

<a name="rendering-roles"></a>
## Rendering Roles

Once you have a `PromptTemplate` instance, you can render any role's content with variable interpolation.

<a name="dynamic-role-methods"></a>
### Dynamic Role Methods

The most expressive way to render a role is to call it as a method directly on the prompt instance. This works for **any** role — not just `system` and `user`:

```php
$prompt = PROMPTDECK::get('code-reviewer');

// Render the system role
$prompt->system(['tone' => 'professional']);

// Render the user role
$prompt->user(['input' => $code]);

// Render custom roles
$prompt->assistant(['context' => $history]);
$prompt->developer(['task' => 'review']);
$prompt->tool(['name' => 'search']);
```

When called without arguments, the raw content is returned with placeholders intact:

```php
$prompt->system(); // "You are a {{ $tone }} AI assistant..."
```

<a name="the-role-method"></a>
### The `role` Method

If the role name is dynamic or stored in a variable, use the explicit `role` method:

```php
$roleName = 'assistant';

$content = $prompt->role($roleName, ['context' => $history]);
```

This is functionally identical to the magic method approach. If the role does not exist, an empty string is returned.

<a name="raw-content"></a>
### Raw Content

To retrieve a role's content **without** variable interpolation, use the `raw` method:

```php
$template = $prompt->raw('system');
// "You are a {{ $tone }} AI assistant specialized in..."
```

This is useful when you need to inspect the template, store it, or perform your own interpolation logic.

<a name="inspecting-prompts"></a>
## Inspecting Prompts

<a name="available-roles"></a>
### Available Roles

The `roles` method returns an array of all role names defined in the prompt:

```php
$prompt->roles();
// ['system', 'user', 'assistant']
```

<a name="checking-for-a-role"></a>
### Checking for a Role

Use the `has` method to check whether a specific role exists before attempting to render it:

```php
if ($prompt->has('assistant')) {
    $content = $prompt->assistant(['context' => $history]);
}
```

<a name="metadata"></a>
### Metadata

Each prompt can carry metadata (stored in `metadata.json` at the version level). Access it via the `metadata` method:

```php
$prompt->metadata();
// ['description' => 'Summarises customer orders', 'author' => 'Alice', ...]
```

Other useful accessors:

```php
$prompt->name();    // 'order-summary'
$prompt->version(); // 2
```

<a name="building-messages-for-ai-apis"></a>
## Building Messages for AI APIs

<a name="all-roles"></a>
### All Roles

The `toMessages` method builds a messages array compatible with OpenAI, Anthropic, and other chat-completion APIs. It renders every role with the given variables and returns them in order:

```php
$messages = PROMPTDECK::get('chat-agent')->toMessages([
    'tone'  => 'helpful',
    'input' => $userMessage,
]);

// [
//     ['role' => 'system',    'content' => 'You are a helpful AI assistant...'],
//     ['role' => 'user',      'content' => 'Please help me with: ...'],
//     ['role' => 'assistant', 'content' => 'Based on the context...'],
// ]
```

This array can be passed directly to an AI SDK:

```php
$response = OpenAI::chat()->create([
    'model'    => 'gpt-4',
    'messages' => $messages,
]);
```

<a name="filtering-roles"></a>
### Filtering Roles

Pass a second argument to limit which roles are included and in what order:

```php
$messages = $prompt->toMessages(
    ['tone' => 'concise'],
    ['system', 'user']  // only include these two roles
);
```

Roles specified in the filter that don't exist in the prompt are silently skipped.

<a name="variable-interpolation"></a>
## Variable Interpolation

<a name="syntax"></a>
### Syntax

Prompt Deck supports two placeholder syntaxes within prompt files:

| Syntax | Example |
|---|---|
| Spaced (recommended) | `{{ $tone }}` |
| Compact | `{{tone}}` |

Both are replaced when you render a role with variables:

```php
// Template: "You are a {{ $tone }} AI assistant. Task: {{task}}"

$prompt->system(['tone' => 'friendly', 'task' => 'summarise']);
// "You are a friendly AI assistant. Task: summarise"
```

<a name="supported-value-types"></a>
### Supported Value Types

Values are cast to strings automatically, so you can pass integers, floats, or any stringable value:

```php
$prompt->system([
    'name'  => 'Alice',      // string
    'count' => 42,            // int → "42"
    'score' => 3.14,          // float → "3.14"
    'price' => '$100.00',     // string with special characters
]);
```

<a name="missing-variables"></a>
### Missing Variables

Placeholders that are not matched by the provided variables are left intact. This lets you render in stages or identify unfilled variables:

```php
$prompt->system(['tone' => 'friendly']);
// "You are a friendly AI assistant. Your role is {{ $role }}."
```

<a name="versioning"></a>
## Versioning

Prompts are versioned using directory-based versioning. Each version lives in its own sub-directory (`v1/`, `v2/`, etc.) inside the prompt folder:

```
resources/prompts/
└── order-summary/
    ├── v1/
    │   ├── system.md
    │   └── user.md
    ├── v2/
    │   ├── system.md
    │   ├── user.md
    │   └── assistant.md
    └── metadata.json
```

<a name="listing-versions"></a>
### Listing Versions

Retrieve all versions for a prompt:

```php
$versions = PROMPTDECK::versions('order-summary');

// [
//     ['version' => 1, 'path' => '...', 'metadata' => [...]],
//     ['version' => 2, 'path' => '...', 'metadata' => [...]],
// ]
```

<a name="activating-a-version"></a>
### Activating a Version

Set a specific version as the active version. When database tracking is enabled, this updates the `prompt_versions` table. Otherwise, it writes to `metadata.json`:

```php
PROMPTDECK::activate('order-summary', 2);
```

<a name="version-resolution"></a>
### Version Resolution

When you call `PROMPTDECK::get('name')` without a version, the active version is resolved in this order:

1. **Database** — if tracking is enabled, the version marked `is_active = true` in the `prompt_versions` table.
2. **metadata.json** — the `active_version` key in the prompt's root `metadata.json` file.
3. **Highest version** — falls back to the highest version number found on disk.

<a name="caching"></a>
## Caching

When caching is enabled, loaded prompts are stored in your configured cache store to avoid repeated filesystem reads:

```php
// config/prompt-deck.php
'cache' => [
    'enabled' => env('PROMPTDECK_CACHE_ENABLED', true),
    'store'   => env('PROMPTDECK_CACHE_STORE', 'file'),
    'ttl'     => 3600, // seconds
],
```

The cache key follows the pattern `prompt-deck.{name}.v{version}`. Prompts are cached on first load and served from cache on subsequent requests until the TTL expires.

> **Note**  
> Set `PROMPTDECK_CACHE_ENABLED=false` in your `.env` during development so file changes are picked up immediately.

<a name="execution-tracking"></a>
## Execution Tracking

When database tracking is enabled, you can log prompt executions for performance monitoring and audit trails:

```php
PROMPTDECK::track('order-summary', 2, [
    'input'    => ['message' => 'Summarise order #1234'],
    'output'   => 'Your order contains...',
    'tokens'   => 150,
    'latency'  => 234.5,
    'cost'     => 0.002,
    'model'    => 'gpt-4',
    'provider' => 'openai',
    'feedback' => ['rating' => 5],
]);
```

All fields except `input` and `output` are optional. Records are inserted into the `prompt_executions` table.

Enable tracking in your configuration:

```php
// config/prompt-deck.php
'tracking' => [
    'enabled'    => env('PROMPTDECK_TRACKING_ENABLED', true),
    'connection' => env('PROMPTDECK_DB_CONNECTION'), // null for default
],
```

Publish the migrations to create the required tables:

```bash
php artisan vendor:publish --tag=prompt-deck-migrations
php artisan migrate
```

<a name="serialisation"></a>
## Serialisation

The `PromptTemplate` class implements Laravel's `Arrayable` contract. Call `toArray()` to get a serialisable representation:

```php
$prompt->toArray();

// [
//     'name'     => 'order-summary',
//     'version'  => 2,
//     'roles'    => ['system' => '...', 'user' => '...'],
//     'metadata' => ['description' => '...'],
// ]
```

This is particularly useful for caching, logging, or passing prompt data to queued jobs.

<a name="configuration"></a>
## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=prompt-deck-config
```

The following options are available in `config/prompt-deck.php`:

| Key | Default | Description |
|---|---|---|
| `path` | `resource_path('prompts')` | Base directory where prompt files are stored. |
| `extension` | `md` | File extension for prompt templates (`md`, `txt`, etc.). |
| `versioning` | `directory` | Versioning strategy (`directory` for `v1/`, `v2/` sub-folders). |
| `cache.enabled` | `true` | Whether to cache loaded prompts. |
| `cache.store` | `file` | Which cache store to use (`file`, `redis`, etc.). |
| `cache.ttl` | `3600` | Cache time-to-live in seconds. |
| `tracking.enabled` | `true` | Enable database tracking of versions and executions. |
| `tracking.connection` | `null` | Database connection name (`null` for default). |
