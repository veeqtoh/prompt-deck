<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Prompts Path
    |--------------------------------------------------------------------------
    |
    | This path determines where your versioned prompt files are stored.
    | By default, they live in 'resources/prompts'.
    |
    */
    'path' => resource_path('prompts'),

    /*
    |--------------------------------------------------------------------------
    | Default Prompt Extension
    |--------------------------------------------------------------------------
    |
    | The file extension used for prompt templates. Markdown is recommended
    | for readability, but you can change it to 'txt', 'blade.php', etc.
    |
    */
    'extension' => 'md',

    /*
    |--------------------------------------------------------------------------
    | Versioning Strategy
    |--------------------------------------------------------------------------
    |
    | How versions are stored: 'directory' (v1/, v2/ inside prompt folder)
    | or 'file' (prompt_v1.md, prompt_v2.md). Default is 'directory'.
    |
    */
    'versioning' => 'directory',

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Enable caching of compiled prompts for better performance.
    |
    */
    'cache' => [
        'enabled' => env('PROMPTFORGE_CACHE_ENABLED', true),
        'store' => env('PROMPTFORGE_CACHE_STORE', 'file'), // null to use default cache
        'ttl' => 3600, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Tracking
    |--------------------------------------------------------------------------
    |
    | If enabled, prompt versions and executions will be logged to the database,
    | enabling performance tracking and audit trails.
    |
    */
    'tracking' => [
        'enabled' => env('PROMPTFORGE_TRACKING_ENABLED', true),
        'connection' => env('PROMPTFORGE_DB_CONNECTION'), // null for default
    ],
];