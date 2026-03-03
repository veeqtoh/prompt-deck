# Laravel AI SDK Integration

PROMPTDECK provides first-class integration with the [Laravel AI SDK](https://laravel.com/docs/ai-sdk), allowing you to load versioned, file-based prompts directly into your AI agents.

## Installation

PROMPTDECK does **not** require the AI SDK — it's listed as a `suggest` dependency. Install it alongside PROMPTDECK when you're ready:

```bash
composer require laravel/ai
```

## Automatic Prompt Scaffolding

When the Laravel AI SDK is installed, PROMPTDECK automatically hooks into the `make:agent` command. Whenever you create a new agent:

```bash
php artisan make:agent SalesCoach
```

PROMPTDECK will detect the successful command and automatically run:

```bash
php artisan make:prompt sales-coach
```

This creates a versioned prompt directory ready for the agent to use via the `HasPromptTemplate` trait — zero extra setup required.

### How It Works

PROMPTDECK registers a listener on Laravel's `CommandFinished` event. When `make:agent` completes successfully, the listener:

1. Extracts the agent name from the command input
2. Converts it to kebab-case (`SalesCoach` → `sales-coach`)
3. Strips any namespace prefix (`App\Ai\Agents\SalesCoach` → `sales-coach`)
4. Checks if the prompt already exists (skips if it does)
5. Runs `make:prompt` with the derived name

The listener is only registered when `laravel/ai` is installed. If the prompt creation fails for any reason, it does **not** break the `make:agent` workflow — the agent is still created successfully.

### Example Output

```
$ php artisan make:agent SalesCoach

   INFO  Agent [app/Ai/Agents/SalesCoach.php] created successfully.

PROMPTDECK: Created prompt sales-coach for SalesCoach.
```

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

That's it. The `HasPromptTemplate` trait provides the `instructions()` method required by the `Agent` contract, loading the system prompt from your PROMPTDECK files.

## How It Works

The trait maps PROMPTDECK concepts to AI SDK contracts:

| PROMPTDECK | AI SDK | Description |
|---|---|---|
| `system.md` role file | `instructions()` | Agent's system prompt |
| `user.md`, `assistant.md` role files | `messages()` | Conversation context |
| `metadata.json` | — | Prompt metadata (description, author) |
| `v1/`, `v2/`, etc. | — | Version management |

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

## Customising the Prompt

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

### Pinning a Version

By default, the active version is loaded. Pin to a specific version:

```php
public function promptVersion(): ?int
{
    return 2; // Always use v2
}
```

### Variable Interpolation

Pass dynamic values into your prompt templates:

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

## Full Agent Example

Here's a complete agent using all PROMPTDECK features:

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
        // Load pre-defined context from PROMPTDECK (user/assistant roles).
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

Create the prompt files:

```bash
php artisan make:prompt sales-coach --roles=system,user
```

Edit `prompts/sales-coach/v1/system.md`:

```markdown
You are a sales coach for {{ $company }}.
You are helping {{ $user_name }} improve their sales technique.

Analyse transcripts carefully and provide:
- Specific, actionable feedback
- A score from 1-10
```

## Conversation Context

If your agent implements `Conversational`, you can load pre-defined conversation context from PROMPTDECK role files:

```php
public function messages(): iterable
{
    // Returns Message[] for all non-system roles (user, assistant, etc.)
    return $this->promptMessages();
}
```

Limit to specific roles:

```php
public function messages(): iterable
{
    return $this->promptMessages(['user']);
}
```

Merge with database conversation history:

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

## Performance Tracking

The `TrackPromptMiddleware` automatically records prompt executions via PROMPTDECK's tracking system. Enable tracking in your config:

```php
// config/prompt-deck.php
'tracking' => [
    'enabled'    => true,
    'connection' => null, // uses default database connection
],
```

Add the middleware to your agent:

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

The middleware records:

| Field | Source |
|---|---|
| `prompt_name` | Agent's `promptName()` |
| `prompt_version` | Resolved template version |
| `input` | The user's prompt text |
| `output` | The AI response text |
| `tokens` | Total token usage |
| `latency_ms` | Round-trip time in milliseconds |
| `model` | Model used (e.g. `gpt-4o`) |
| `provider` | Provider used (e.g. `openai`) |

## Accessing the Template Directly

You can access the full `PromptTemplate` object for advanced use cases:

```php
// Get the template instance.
$template = $agent->promptTemplate();

// Check available roles.
$template->roles();       // ['system', 'user', 'assistant']
$template->has('skill');  // false

// Get raw content (no interpolation).
$template->raw('system');

// Get the resolved version.
$template->version();     // 2

// Clear the cached template (useful in tests or long-running processes).
$agent->forgetPromptTemplate();
```

## Without the AI SDK

The `HasPromptTemplate` trait works even without `laravel/ai` installed. The `instructions()` method simply returns a string, and `promptMessages()` falls back to returning raw arrays:

```php
// Without laravel/ai — returns array
$messages = $agent->promptMessages();
// [['role' => 'user', 'content' => '...'], ...]

// With laravel/ai — returns Message[]
$messages = $agent->promptMessages();
// [Message('user', '...'), ...]
```

This allows you to use PROMPTDECK's template loading in any context, not just with the AI SDK.

## API Reference

### HasPromptTemplate Trait

| Method | Returns | Description |
|---|---|---|
| `promptName()` | `string` | Prompt name (default: kebab-cased class name) |
| `promptVersion()` | `?int` | Version to load (`null` = active) |
| `promptVariables()` | `array` | Variables for interpolation |
| `promptTemplate()` | `PromptTemplate` | The loaded template (cached) |
| `instructions()` | `string` | System role content (for `Agent` contract) |
| `promptMessages(?array $only)` | `array` | Non-system roles as messages |
| `forgetPromptTemplate()` | `static` | Clear the cached template |

### TrackPromptMiddleware

| Method | Description |
|---|---|
| `handle($prompt, $next)` | Wraps the agent call and records execution data |
