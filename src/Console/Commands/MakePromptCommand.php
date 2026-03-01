<?php

declare(strict_types=1);

namespace Veeqtoh\PromptForge\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class MakePromptCommand extends Command
{
    protected $signature = 'make:prompt {name : The name of the prompt}
                            {--from= : Path to a stub file to use as template}
                            {--no-system : Skip creating a system prompt file}
                            {--force : Overwrite existing prompt}';

    protected $description = 'Create a new prompt structure for your AI agent';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle(): int
    {
        $name     = $this->toKebabCase($this->argument('name'));
        $basePath = config('prompt-forge.path');

        if (! is_dir($basePath)) {
            $this->files->makeDirectory($basePath, 0755, true);
        }

        $promptPath  = "{$basePath}/{$name}";
        $versionPath = "{$promptPath}/v1";

        if ($this->files->exists($versionPath) && ! $this->option('force')) {
            $this->error("Prompt [{$name}] already exists. Use --force to overwrite.");

            return Command::FAILURE;
        }

        // Create directories (skip if they already exist, e.g. when --force is used).
        if (! $this->files->isDirectory($versionPath)) {
            $this->files->makeDirectory($versionPath, 0755, true);
        }

        // Determine file extension
        $extension = config('prompt-forge.extension', 'md');

        // Create user prompt file
        $userFile    = "{$versionPath}/user.{$extension}";
        $stubContent = $this->getStubContent($this->option('from'));
        $this->files->put($userFile, $stubContent);

        // Create system prompt unless --no-system is passed.
        if (! $this->option('no-system')) {
            $systemFile = "{$versionPath}/system.{$extension}";
            $this->files->put($systemFile, $this->getSystemStubContent());
        }

        // Create metadata.json
        $metadata = [
            'name'        => $name,
            'description' => '',
            'variables'   => [],
            'created_at'  => now()->toIso8601String(),
        ];
        $this->files->put("{$promptPath}/metadata.json", json_encode($metadata, JSON_PRETTY_PRINT));

        $this->info("Prompt [{$name}] created successfully at version 1.");

        return Command::SUCCESS;
    }

    protected function getStubContent(?string $customStub): string
    {
        if ($customStub && $this->files->exists($customStub)) {
            return $this->files->get($customStub);
        }

        return $this->files->get($this->resolveStubPath('user-prompt.stub'));
    }

    protected function getSystemStubContent(): string
    {
        return $this->files->get($this->resolveStubPath('system-prompt.stub'));
    }

    /**
     * Resolve the path to a stub file.
     *
     * If the user has published stubs, use those; otherwise fall back to
     * the package defaults.
     */
    protected function resolveStubPath(string $stub): string
    {
        $publishedPath = $this->laravel->basePath("stubs/prompt-forge/{$stub}");

        if ($this->files->exists($publishedPath)) {
            return $publishedPath;
        }

        return __DIR__.'/../../../stubs/'.$stub;
    }

    /**
     * Convert a string to kebab-case.
     *
     * Handles PascalCase, camelCase, snake_case, and mixed inputs.
     */
    protected function toKebabCase(string $value): string
    {
        // Insert a hyphen before uppercase letters that follow a lowercase letter or digit.
        $value = (string) preg_replace('/([a-z\d])([A-Z])/', '$1-$2', $value);

        // Insert a hyphen between consecutive uppercase letters followed by a lowercase letter (e.g. "XMLParser" → "XML-Parser").
        $value = (string) preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1-$2', $value);

        // Replace underscores, spaces, and multiple hyphens with a single hyphen.
        $value = (string) preg_replace('/[_\s]+/', '-', $value);
        $value = (string) preg_replace('/-{2,}/', '-', $value);

        return strtolower(trim($value, '-'));
    }
}
