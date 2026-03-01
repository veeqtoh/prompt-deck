<?php

declare(strict_types=1);

namespace Veeqtoh\PromptForge\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class MakePromptCommand extends Command
{
    protected $signature = 'make:prompt {name? : The name of the prompt}
                            {--from= : Path to a stub file to use as template}
                            {--u|user : Also create a user prompt file}
                            {--role=* : Additional roles to create prompt files for (e.g. assistant, developer, tool)}
                            {--i|interactive : Interactively choose additional roles}
                            {--f|force : Overwrite existing prompt}';

    protected $description = 'Create a new prompt structure for your AI agent';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle(): int
    {
        $promptedForName = ! $this->argument('name');
        $rawName         = $this->argument('name') ?? $this->ask('What should the prompt be named?');

        if (! $rawName) {
            $this->error('A prompt name is required.');

            return Command::FAILURE;
        }

        $name     = $this->toKebabCase($rawName);
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

        // Create directories (skip if they already exist, e.g. when --force or -f is used).
        if (! $this->files->isDirectory($versionPath)) {
            $this->files->makeDirectory($versionPath, 0755, true);
        }

        // Determine file extension
        $extension = config('prompt-forge.extension', 'md');

        // Create system prompt file (always created by default).
        $systemFile = "{$versionPath}/system.{$extension}";
        $this->files->put($systemFile, $this->getSystemStubContent());

        // Create user prompt file when --user is passed or interactively confirmed.
        $createUser = $this->option('user')
            || ($promptedForName && $this->confirm('Would you also like to create a user prompt file?'));

        if ($createUser) {
            $userFile    = "{$versionPath}/user.{$extension}";
            $stubContent = $this->getStubContent($this->option('from'));
            $this->files->put($userFile, $stubContent);
        }

        // Handle additional roles (auto-interactive when name was prompted).
        $roles = $this->resolveExtraRoles($promptedForName);

        foreach ($roles as $role) {
            $roleName = $this->toKebabCase($role);
            $roleFile = "{$versionPath}/{$roleName}.{$extension}";
            $this->files->put($roleFile, $this->getRoleStubContent($roleName));
        }

        // Create metadata.json
        $allRoles = ['system'];

        if ($createUser) {
            $allRoles[] = 'user';
        }

        $allRoles = array_merge($allRoles, array_map([$this, 'toKebabCase'], $roles));

        $metadata = [
            'name'        => $name,
            'description' => '',
            'roles'       => array_values($allRoles),
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
     * Get the rendered content for an extra role prompt file.
     *
     * The generic role stub's {{ $role }} placeholder is replaced at
     * scaffolding time so each file is personalised to its role.
     */
    protected function getRoleStubContent(string $role): string
    {
        $content = $this->files->get($this->resolveStubPath('role-prompt.stub'));

        return str_replace('{{ $role }}', $role, $content);
    }

    /**
     * Determine the extra roles to scaffold.
     *
     * Uses the --role option values when provided; otherwise, in an
     * interactive terminal, prompts the user with a free-text input.
     *
     * @return list<string>
     */
    protected function resolveExtraRoles(bool $autoInteractive = false): array
    {
        /** @var list<string> $roles */
        $roles = $this->option('role');

        if (! empty($roles)) {
            return array_values(array_filter(array_map('trim', $roles)));
        }

        if (! $autoInteractive && ! $this->option('interactive')) {
            return [];
        }

        if (! $this->confirm('Would you like to create prompt files for additional roles?')) {
            return [];
        }

        $input = $this->ask('Which roles? (comma-separated, e.g. assistant,developer)');

        if (! $input) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $input))));
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
