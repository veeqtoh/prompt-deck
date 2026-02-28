<?php

declare(strict_types=1);

namespace Veeqtoh\PromptForge\Exceptions;

class PromptNotFoundException extends PromptForgeException
{
    /**
     * Create a new exception instance for a missing prompt.
     *
     * @param  string  $name  The name of the missing prompt.
     */
    public static function named(string $name): self
    {
        return new self("Prompt [{$name}] not found.");
    }
}
