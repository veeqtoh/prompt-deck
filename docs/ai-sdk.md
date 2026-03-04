# Laravel AI SDK Integration

- [Introduction](#introduction)
- [Installation](#installation)
- [Automatic Prompt Scaffolding](#automatic-prompt-scaffolding)
    - [How It Works](#how-scaffolding-works)
    - [Example Output](#example-output)
    - [Disabling Auto-Scaffolding](#disabling-auto-scaffolding)
- [Quick Start](#quick-start)
- [The HasPromptTemplate Trait](#the-hasprompttemplate-trait)
    - [How It Maps to AI SDK Contracts](#how-it-maps)
    - [Mapping Diagram](#mapping-diagram)
- [Customising the Prompt](#customising-the-prompt)
    - [Prompt Name](#prompt-name)
    - [Pinning a Version](#pinning-a-version)
    - [Variable Interpolation](#variable-interpolation)
- [Full Agent Example](#full-agent-example)
- [Conversation Context](#conversation-context)
    - [Loading All Non-System Roles](#loading-all-non-system-roles)
    - [Limiting to Specific Roles](#limiting-to-specific-roles)
    - [Merging with Database History](#merging-with-database-history)
- [Performance Tracking Middleware](#performance-tracking-middleware)
    - [Setting Up the Middleware](#setting-up-the-middleware)
    - [What Gets Tracked](#what-gets-tracked)
    - [How It Works Internally](#how-middleware-works)
- [Accessing the Template Directly](#accessing-the-template-directly)
- [Clearing the Cached Template](#clearing-the-cached-template)
- [Without the AI SDK](#without-the-ai-sdk)
- [API Reference](#api-reference)

<a name="introduction"></a>
## Introduction

Prompt Deck provides first-class, optional integration with the [Laravel AI SDK](https://laravel.com/docs/ai-sdk). When the AI SDK is installed, you get:

- **Automatic prompt scaffolding** — Running `make:agent` automatically creates a matching prompt directory.
- **The `HasPromptTemplate` trait** — Provides `instructions()` and `promptMessages()` methods that load versioned prompts directly into your AI agents.
- **The `TrackPromptMiddleware`** — Automatically records prompt executions (tokens, latency, model, etc.) using Prompt Deck's tracking system.

All of this is entirely optional. Prompt Deck works perfectly without the AI SDK.

<a name="installation"></a>
## Installation

Prompt Deck does **not** require the AI SDK — it's listed as a `suggest` dependency. Install it when you're ready:

```bash
composer require laravel/ai
```

Once `laravel/ai` is installed, Prompt Deck's AI SDK features activate automatically. No additional configuration is needed.

<a name="automatic-prompt-scaffolding"></a>
## Automatic Prompt Scaffolding

When the Laravel AI SDK is installed, Prompt Deck automatically hooks into the `make:agent` command. Whenever you create a new agent:

```bash
php artisan make:agent SalesCoach
```

Prompt Deck detects the successful command and automatically runs:

```bash
php artisan make:prompt sales-coach
```

This creates a versioned prompt directory ready for the agent to use via the `HasPromptTemplate` trait — zero extra setup required.

<a name="how-scaffolding-works"></a>
### How It Works

Prompt Deck registers a listener (`AfterMakeAgent`) on Laravel's `CommandFinished` event. When `make:agent` completes successfully, the listener:

1. Extracts the agent name from the command input.
2. Converts it to kebab-case (`SalesCoach` → `sales-coach`).
3. Strips any namespace prefix (`App\Ai\Agents\SalesCoach` → `sales-coach`).
4. Checks if the prompt already exists (skips if it does).
5. Runs `make:prompt` with the derived name.

The listener is only registered when `laravel/ai` is installed. If the prompt creation fails for any reason, it does **not** break the `make:agent` workflow — the agent is still created successfully.

<a name="example-output"></a>
### Example Output

```
$ php artisan make:agent SalesCoach

   INFO  Agent [app/Ai/Agents/SalesCoach.php] created successfully.

PromptDeck: Created prompt sales-coach for SalesCoach.
```

<a name="disabling-auto-scaffolding"></a>
### Disabling Auto-Scaffolding

To disable automatic prompt scaffolding, set the configuration option:

```php
// config/prompt-deck.php
'scaffold_on_make_agent' => false,
```

Or via environment variable:

```dotenv
PROMPTDECK_SCAFFOLD_ON_MAKE_AGENT=false
```

<a name="quick-start"></a>
## Quick Start

Use the `HasPromptTemplate` trait on any agent class:

```php
<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Veeqtoh\PromptDeck\Concerns\HasPromptTemplate;

class SalesCoach implements Agent
{
    use Promptable, HasPromptTemplate;

    // instructions() is provided automatically by HasPromptTemplate.
    // It loads prompts/sales-coach/v{active}/system.md
}
```

That's it. The `HasPromptTemplate` trait provides the `instructions()` method required by the `Agent` contract, loading the system prompt from your Prompt Deck files.

<a name="the-hasprompttemplate-trait"></a>
## The HasPromptTemplate Trait

The `HasPromptTemplate` trait bridges Prompt Deck's file-based templates with the Laravel AI SDK's agent contracts.

<a name="how-it-maps"></a>
### How It Maps to AI SDK Contracts

| Prompt Deck | AI SDK | Description |
|---|---|---|
| `system.md` role file | `instructions()` | Agent's system prompt. |
| `user.md`, `assistant.md`, etc. | `messages()` via `promptMessages()` | Conversation context. |
| `metadata.json` | — | Prompt metadata (description, variables, etc.). |
| `v1/`, `v2/`, etc. | — | Version management. |

<a name="mapping-diagram"></a>
### Mapping Diagram

```
prompts/sales-coach/
├── v1/
│   ├── system.md          → instructions()
│   ├── user.md            → promptMessages()
│   └── metadata.json
└── v2/
    ├── system.md          → instructions()  (active version)
    ├── user.md            → promptMessages()
    ├── assistant.md       → promptMessages()
    └── metadata.json
```

<a name="customising-the-prompt"></a>
## Customising the Prompt

<a name="prompt-name"></a>
### Prompt Name

By default, the prompt name is derived from the class name in kebab-case:

- `SalesCoach` → `sales-coach`
- `DocumentAnalyzer` → `document-analyzer`

Override `promptName()` to use a custom name:

```php
class SalesCoach implements Agent
{
    use Promptable, HasPromptTemplate;

    public function promptName(): string
    {
        return 'coaching/sales'; // loads from prompts/coaching/sales/
    }
}
```

<a name="pinning-a-version"></a>
### Pinning a Version

By default, the active version is loaded. Pin to a specific version by overriding `promptVersion()`:

```php
public function promptVersion(): ?int
{
    return 2; // Always use v2
}
```

Return `null` (the default) to always load the active version — useful for A/B testing and gradual rollouts.

<a name="variable-interpolation"></a>
### Variable Interpolation

Pass dynamic values into your prompt templates by overriding `promptVariables()`:

```php
class SalesCoach implements Agent
{
    use Promptable, HasPromptTemplate;

    public function __construct(public User $user) {}

    public function promptVariables(): array
    {
        return [
            'user_name' => $this->user->name,
            'company'   => $this->user->company,
        ];
    }
}
```

In your `system.md`:

```markdown
You are a sales coach for {{ $company }}.
You are helping {{ $user_name }} improve their technique.
```

Variables are interpolated into **all** roles (system, user, assistant, etc.) when accessed via `instructions()` or `promptMessages()`.

<a name="full-agent-example"></a>
## Full Agent Example

Here's a complete agent using all Prompt Deck features with the AI SDK:

```php
<?php

namespace App\Ai\Agents;

use App\Ai\Tools\RetrievePreviousTranscripts;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasMiddleware;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Veeqtoh\PromptDeck\Ai\TrackPromptMiddleware;
use Veeqtoh\PromptDeck\Concerns\HasPromptTemplate;

class SalesCoach implements Agent, Conversational, HasTools, HasStructuredOutput, HasMiddleware
{
    use Promptable, HasPromptTemplate;

    public function __construct(public User $user) {}

    // instructions() is provided by HasPromptTemplate — no need to define it.

    /**
     * Dynamic variables injected into the prompt template.
     */
    public function promptVariables(): array
    {
        return [
            'user_name' => $this->user->name,
            'company'   => $this->user->company,
        ];
    }

    /**
     * Conversation context from the prompt template plus history.
     */
    public function messages(): iterable
    {
        return $this->promptMessages();
    }

    /**
     * Tools available to the agent.
     */
    public function tools(): iterable
    {
        return [
            new RetrievePreviousTranscripts,
        ];
    }

    /**
     * Structured output schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'feedback' => $schema->string()->required(),
            'score'    => $schema->integer()->min(1)->max(10)->required(),
        ];
    }

    /**
     * Middleware for automatic tracking.
     */
    public function middleware(): array
    {
        return [
            new TrackPromptMiddleware,
        ];
    }
}
```

Create and populate the prompt files:

```bash
php artisan make:prompt sales-coach --user
```

Edit `prompts/sales-coach/v1/system.md`:

```markdown
You are a sales coach for {{ $company }}.
You are helping {{ $user_name }} improve their sales technique.

Analyse transcripts carefully and provide:
- Specific, actionable feedback
- A score from 1-10
```

<a name="conversation-context"></a>
## Conversation Context

If your agent implements `Conversational`, you can load pre-defined conversation context from Prompt Deck role files using the `promptMessages()` method.

<a name="loading-all-non-system-roles"></a>
### Loading All Non-System Roles

By default, `promptMessages()` returns all roles **except** `system` (which goes through `instructions()`):

```php
public function messages(): iterable
{
    // Returns Message[] for all non-system roles (user, assistant, etc.)
    return $this->promptMessages();
}
```

<a name="limiting-to-specific-roles"></a>
### Limiting to Specific Roles

Pass an array to limit which roles are included:

```php
public function messages(): iterable
{
    return $this->promptMessages(['user']);
}
```

<a name="merging-with-database-history"></a>
### Merging with Database History

Combine template messages with conversation history from your database:

```php
use Laravel\Ai\Messages\Message;

public function messages(): iterable
{
    // Pre-defined context from the prompt template.
    $context = $this->promptMessages();

    // Plus conversation history from the database.
    $history = History::where('user_id', $this->user->id)
        ->latest()
        ->limit(50)
        ->get()
        ->reverse()
        ->map(fn ($m) => new Message($m->role, $m->content))
        ->all();

    return array_merge($context, $history);
}
```

<a name="performance-tracking-middleware"></a>
## Performance Tracking Middleware

The `TrackPromptMiddleware` automatically records prompt executions via Prompt Deck's tracking system.

<a name="setting-up-the-middleware"></a>
### Setting Up the Middleware

1. **Enable tracking** in your configuration:

```php
// config/prompt-deck.php
'tracking' => [
    'enabled'    => true,
    'connection' => null, // uses default database connection
],
```

2. **Publish and run the migrations** (if you haven't already):

```bash
php artisan vendor:publish --tag=prompt-deck-migrations
php artisan migrate
```

3. **Add the middleware** to your agent:

```php
use Laravel\Ai\Contracts\HasMiddleware;
use Veeqtoh\PromptDeck\Ai\TrackPromptMiddleware;

class SalesCoach implements Agent, HasMiddleware
{
    use Promptable, HasPromptTemplate;

    public function middleware(): array
    {
        return [
            new TrackPromptMiddleware,
        ];
    }
}
```

<a name="what-gets-tracked"></a>
### What Gets Tracked

The middleware automatically records the following fields to the `prompt_executions` table:

| Field | Source |
|---|---|
| `prompt_name` | Agent's `promptName()` method. |
| `prompt_version` | Resolved template version number. |
| `input` | The user's prompt text from the `AgentPrompt`. |
| `output` | The AI response text. |
| `tokens` | Total token usage from the response. |
| `latency_ms` | Round-trip time in milliseconds (measured via `hrtime`). |
| `model` | Model used (e.g. `gpt-4o`, `claude-3-sonnet`). |
| `provider` | Provider name (e.g. `openai`, `anthropic`). |

<a name="how-middleware-works"></a>
### How It Works Internally

The middleware:
1. Records the start time before the request using `hrtime(true)`.
2. Passes the prompt to the next middleware in the pipeline.
3. Uses the response's `then()` hook to record execution data after the response completes.
4. Calls `PromptManager::track()` with the collected data.

The middleware only tracks agents that use the `HasPromptTemplate` trait. If the agent doesn't have a `promptName()` method, the tracking is silently skipped.

<a name="accessing-the-template-directly"></a>
## Accessing the Template Directly

You can access the full `PromptTemplate` object from within your agent for advanced use cases:

```php
// Get the template instance.
$template = $this->promptTemplate();

// Check available roles.
$template->roles();       // ['system', 'user', 'assistant']
$template->has('skill');  // false

// Get raw content (no interpolation).
$template->raw('system');

// Get the resolved version.
$template->version();     // 2

// Get prompt metadata.
$template->metadata();    // ['description' => '...', ...]
```

The template instance is cached for the lifetime of the agent object, so repeated calls to `promptTemplate()` don't incur additional filesystem or cache lookups.

<a name="clearing-the-cached-template"></a>
## Clearing the Cached Template

Clear the cached template to force a fresh load on next access:

```php
$agent->forgetPromptTemplate();
```

This is useful in:
- **Long-running processes** (queue workers, daemons) where prompts might change between jobs.
- **Tests** where you switch prompt versions between assertions.

The method returns `$this` for fluent chaining:

```php
$agent->forgetPromptTemplate()->promptTemplate(); // fresh load
```

<a name="without-the-ai-sdk"></a>
## Without the AI SDK

The `HasPromptTemplate` trait works even without `laravel/ai` installed. The `instructions()` method simply returns a string, and `promptMessages()` falls back to returning raw arrays instead of AI SDK `Message` objects:

```php
// Without laravel/ai — returns array
$messages = $agent->promptMessages();
// [['role' => 'user', 'content' => '...'], ...]

// With laravel/ai — returns Message[]
$messages = $agent->promptMessages();
// [Message('user', '...'), ...]
```

This allows you to use Prompt Deck's template loading in any context, not just with the AI SDK.

<a name="api-reference"></a>
## API Reference

### HasPromptTemplate Trait

| Method | Returns | Description |
|---|---|---|
| `promptName()` | `string` | Prompt name (default: kebab-cased class name). |
| `promptVersion()` | `?int` | Version to load (`null` = active). |
| `promptVariables()` | `array` | Variables for interpolation. |
| `promptTemplate()` | `PromptTemplate` | The loaded template (cached per instance). |
| `instructions()` | `Stringable\|string` | System role content (satisfies `Agent` contract). |
| `promptMessages(?array $only)` | `array` | Non-system roles as `Message` objects (or raw arrays). |
| `forgetPromptTemplate()` | `static` | Clear the cached template. |

### TrackPromptMiddleware

| Method | Description |
|---|---|
| `handle($prompt, $next)` | Wraps the agent call, measures latency, and records execution data via `PromptManager::track()`. |

### AfterMakeAgent Listener

| Method | Description |
|---|---|
| `handle(CommandFinished $event)` | Detects `make:agent` completion and scaffolds a matching prompt. |
