<?php

declare(strict_types=1);

namespace Veeqtoh\PromptDeck\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Veeqtoh\PromptDeck\PromptTemplate get(string $name, ?int $version = null)
 * @method static \Veeqtoh\PromptDeck\PromptTemplate active(string $name)
 * @method static array versions(string $name)
 * @method static bool activate(string $name, int $version)
 * @method static void track(string $promptName, int $version, array $data)
 *
 * @see \Veeqtoh\PromptDeck\PromptManager
 */
class PROMPTDECK extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'prompt-deck';
    }
}
