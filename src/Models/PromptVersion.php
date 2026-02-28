<?php

declare(strict_types=1);

namespace Veeqtoh\PromptForge\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromptVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'version',
        'system_prompt',
        'user_prompt',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'version'   => 'integer',
        'is_active' => 'boolean',
        'metadata'  => 'array',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Veeqtoh\PromptForge\Database\Factories\PromptVersionFactory
    {
        return \Veeqtoh\PromptForge\Database\Factories\PromptVersionFactory::new();
    }
}
