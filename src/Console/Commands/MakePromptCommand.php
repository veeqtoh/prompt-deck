<?php

declare(strict_types=1);

namespace Veeqtoh\PromptForge\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class MakePromptCommand extends Command
{
    protected $signature = 'prompt:make {name : The name of the prompt}
                            {--from= : Path to a stub file to use as template}
                            {--system : Create a system prompt file as well}
                            {--force : Overwrite existing prompt}';

    protected $description = 'Create a new prompt structure';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle(): int
    {
        $name     = $this->argument('name');
        $basePath = config('prompt-forge.path');

        if (! is_dir($basePath)) {
            $this->files->makeDirectory($basePath, 0755, true);
        }

        $promptPath  = $basePath.'/'.$name;
        $versionPath = $promptPath.'/v1';

        if ($this->files->exists($versionPath) && ! $this->option('force')) {
            $this->error("Prompt [{$name}] already exists. Use --force to overwrite.");

            return Command::FAILURE;
        }

        // Create directories
        $this->files->makeDirectory($versionPath, 0755, true);

        // Determine file extension
        $extension = config('prompt-forge.extension', 'md');

        // Create user prompt file
        $userFile    = $versionPath.'/user.'.$extension;
        $stubContent = $this->getStubContent($this->option('from'));
        $this->files->put($userFile, $stubContent);

        // Create system prompt if requested
        if ($this->option('system')) {
            $systemFile = $versionPath.'/system.'.$extension;
            $this->files->put($systemFile, $this->getSystemStubContent());
        }

        // Create metadata.json
        $metadata = [
            'name'        => $name,
            'description' => '',
            'variables'   => [],
            'created_at'  => now()->toIso8601String(),
        ];
        $this->files->put($promptPath.'/metadata.json', json_encode($metadata, JSON_PRETTY_PRINT));

        $this->info("Prompt [{$name}] created successfully at version 1.");

        return Command::SUCCESS;
    }

    protected function getStubContent(?string $customStub): string
    {
        if ($customStub && $this->files->exists($customStub)) {
            return $this->files->get($customStub);
        }

        return <<<'EOT'
# User prompt for {{ $name }}

Your task is to...

User input: {{ $input }}

EOT;
    }

    protected function getSystemStubContent(): string
    {
        return <<<'EOT'
You are an AI assistant specialized in...

Follow these guidelines:
- Be helpful
- Use {{ $tone }} tone

EOT;
    }
}
