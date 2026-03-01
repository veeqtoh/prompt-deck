<?php

declare(strict_types=1);

namespace Veeqtoh\PromptForge\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Veeqtoh\PromptForge\Prompt get(string $name, ?int $version = null)
 * @method static \Veeqtoh\PromptForge\Prompt active(string $name)
 * @method static array versions(string $name)
 * @method static bool activate(string $name, int $version)
 * @method static void track(string $promptName, int $version, array $data)
 *
 * @see \Veeqtoh\PromptForge\PromptManager
 */
class Prompt extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'prompt-forge';
    }
}
