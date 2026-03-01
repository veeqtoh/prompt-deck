<?php

declare(strict_types=1);

namespace Veeqtoh\PromptForge\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Veeqtoh\PromptForge\PromptTemplate get(string $name, ?int $version = null)
 * @method static \Veeqtoh\PromptForge\PromptTemplate active(string $name)
 * @method static array versions(string $name)
 * @method static bool activate(string $name, int $version)
 * @method static void track(string $promptName, int $version, array $data)
 *
 * @see \Veeqtoh\PromptForge\PromptManager
 */
class PromptForge extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'prompt-forge';
    }
}
