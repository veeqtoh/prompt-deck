<?php

declare(strict_types=1);

namespace Veeqtoh\PromptForge;

use Illuminate\Contracts\Support\Arrayable;

class Prompt implements Arrayable
{
    public function __construct(
        protected string $name,
        protected int $version,
        protected string $system,
        protected string $user,
        protected array $metadata = []
    ) {}

    /**
     * Render the system prompt with variables.
     */
    public function renderSystem(array $variables = []): string
    {
        return $this->interpolate($this->system, $variables);
    }

    /**
     * Render the user prompt with variables.
     */
    public function renderUser(array $variables = []): string
    {
        return $this->interpolate($this->user, $variables);
    }

    /**
     * Get the active version number.
     */
    public function version(): int
    {
        return $this->version;
    }

    /**
     * Get the prompt name.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Get all metadata.
     */
    public function metadata(): array
    {
        return $this->metadata;
    }

    /**
     * Convert to array (useful for caching).
     */
    public function toArray(): array
    {
        return [
            'name'     => $this->name,
            'version'  => $this->version,
            'system'   => $this->system,
            'user'     => $this->user,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Simple variable interpolation using {{ $var }} syntax.
     * You could also use Blade, but that adds complexity.
     */
    protected function interpolate(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $content = str_replace(
                ['{{ $'.$key.' }}', '{{'.$key.'}}'],
                (string) $value,
                $content
            );
        }

        return $content;
    }
}
