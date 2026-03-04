# Installation

- [Requirements](#requirements)
- [Installing Prompt Deck](#installing-prompt-deck)
- [Publishing the Configuration](#publishing-the-configuration)
- [Publishing Migrations](#publishing-migrations)
- [Environment Variables](#environment-variables)
- [Verifying the Installation](#verifying-the-installation)

<a name="requirements"></a>
## Requirements

Prompt Deck has the following requirements:

| Dependency | Version |
|---|---|
| PHP | `^8.2` |
| Laravel | `^11.0` ++ |

*Note* that the `sebastian/diff` package is required and is used for the `prompt:diff` command. It may conflict with your PHPUnit/Pest PHP installation so ypu may need to upgrade these dependencies to the latest version.

### Optional Dependencies

| Package | Purpose |
|---|---|
| `laravel/ai` | Enables deep integration with Laravel AI SDK agents to support automatic instructions loading, prompt version tracking in conversations, and auto-scaffolding prompts when creating agents. |

<a name="installing-prompt-deck"></a>
## Installing Prompt Deck

Install the package via Composer:

```bash
composer require veeqtoh/prompt-deck
```

The package uses Laravel's auto-discovery, so the service provider and facade are registered automatically. No manual registration is needed.

If you have disabled auto-discovery, add the provider and facade to your `bootstrap/providers.php` (Laravel 11+) or `config/app.php`:

```php
// bootstrap/providers.php (Laravel 11+)
return [
    // ...
    Veeqtoh\PromptDeck\Providers\PromptDeckServiceProvider::class,
];
```

Or for `config/app.php`:

```php
'providers' => [
    // ...
    Veeqtoh\PromptDeck\Providers\PromptDeckServiceProvider::class,
],

'aliases' => [
    // ...
    'PromptDeck' => Veeqtoh\PromptDeck\Facades\PromptDeck::class,
],
```

<a name="publishing-the-configuration"></a>
## Publishing the Configuration

Publish the configuration file to customise Prompt Deck's behaviour:

```bash
php artisan vendor:publish --tag=prompt-deck-config
```

This creates `config/prompt-deck.php` in your application. See the [Configuration](configuration.md) documentation for a full reference of all available options.

<a name="publishing-migrations"></a>
## Publishing Migrations

If you plan to use database tracking for prompt versions and execution logging, publish and run the migrations:

```bash
php artisan vendor:publish --tag=prompt-deck-migrations
php artisan migrate
```

This creates two tables:

| Table | Purpose |
|---|---|
| `prompt_versions` | Stores prompt version records and tracks which version is active. |
| `prompt_executions` | Logs individual prompt executions with tokens, latency, cost, and feedback data. |

> **Note**
> Database tracking is optional. Prompt Deck works fully without it as version activation falls back to `metadata.json` files, and execution tracking is simply disabled.

> If you prefer, you may publish both the configuration and migration files by running a single command.
```bash
php artisan vendor:publish --provider="Veeqtoh\PromptDeck\Providers\PromptDeckServiceProvider"
```

<a name="environment-variables"></a>
## Environment Variables

Add the following to your `.env` file to configure Prompt Deck's runtime behaviour:

```dotenv
# Cache
PROMPTDECK_CACHE_ENABLED=true          # Enable/disable prompt caching (default: true in production, false when APP_DEBUG=true)
PROMPTDECK_CACHE_STORE=file            # Cache store to use (file, redis, memcached, etc.)
PROMPTDECK_CACHE_TTL=3600              # Cache time-to-live in seconds
PROMPTDECK_CACHE_PREFIX=prompt-deck:   # Cache key prefix

# Database Tracking
PROMPTDECK_TRACKING_ENABLED=true       # Enable/disable database tracking (default: true in production, false when APP_DEBUG=true)
PROMPTDECK_DB_CONNECTION=              # Database connection name (null for default)

# AI SDK Integration
PROMPTDECK_SCAFFOLD_ON_MAKE_AGENT=true # Auto-create prompt when running make:agent
```

All environment variables are optional as sensible defaults are provided.

<a name="verifying-the-installation"></a>
## Verifying the Installation

After installation, verify everything is working:

```bash
# Create your first prompt
php artisan make:prompt hello-world

# List all prompts
php artisan prompt:list

# Test the prompt
php artisan prompt:test hello-world
```

You should see the scaffolded prompt structure in `resources/prompts/hello-world/`.

### What's Next?

- [Configuration](configuration.md) — Customise Prompt Deck's behaviour.
- [Creating Prompts](make-prompt.md) — Learn the full `make:prompt` command.
- [Working with Prompts](prompts.md) — Load, render, and manage prompts in your code.
- [Artisan Commands](commands.md) — All available Artisan commands.
- [Laravel AI SDK Integration](ai-sdk.md) — Use prompts with AI agents.
