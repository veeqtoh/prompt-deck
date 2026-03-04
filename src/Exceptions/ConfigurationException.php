<?php

declare(strict_types=1);

namespace Veeqtoh\PromptDeck\Exceptions;

class ConfigurationException extends PROMPTDECKException
{
    /**
     * Create a new exception for an invalid prompts path configuration.
     *
     * @param string $path The invalid prompts path.
     */
    public static function invalidPath(string $path): self
    {
        return new self("Prompts path [{$path}] is not a directory or is not writable.");
    }
}
