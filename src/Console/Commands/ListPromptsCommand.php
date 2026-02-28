<?php

declare(strict_types=1);

namespace Veeqtoh\PromptForge\Console\Commands;

use Illuminate\Console\Command;
use Veeqtoh\PromptForge\PromptManager;

class ListPromptsCommand extends Command
{
    protected $signature = 'prompt:list {--all : Show all versions for each prompt}';

    protected $description = 'List all available prompts';

    protected PromptManager $manager;

    public function __construct(PromptManager $manager)
    {
        parent::__construct();
        $this->manager = $manager;
    }

    public function handle(): int
    {
        $basePath = config('promptforge.path');
        if (! is_dir($basePath)) {
            $this->warn('Prompts directory not found.');

            return Command::SUCCESS;
        }

        $prompts = glob($basePath.'/*', GLOB_ONLYDIR);
        if (empty($prompts)) {
            $this->info('No prompts found.');

            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($prompts as $promptDir) {
            $name          = basename($promptDir);
            $activeVersion = $this->getActiveVersion($name);

            if ($this->option('all')) {
                $versions = $this->manager->versions($name);
                foreach ($versions as $v) {
                    $rows[] = [
                        $name,
                        'v'.$v['version'],
                        $v['version'] === $activeVersion ? '✅' : '',
                        $v['metadata']['description'] ?? '',
                    ];
                }
            } else {
                $rows[] = [
                    $name,
                    'v'.$activeVersion,
                    '✅',
                    $this->getDescription($name, $activeVersion),
                ];
            }
        }

        $this->table(['Prompt', 'Active Version', 'Active', 'Description'], $rows);

        return Command::SUCCESS;
    }

    protected function getActiveVersion(string $name): int
    {
        try {
            return $this->manager->active($name)->version();
        } catch (\Exception $e) {
            return 0;
        }
    }

    protected function getDescription(string $name, int $version): string
    {
        try {
            $prompt = $this->manager->prompt($name, $version);

            return $prompt->metadata()['description'] ?? '';
        } catch (\Exception $e) {
            return '';
        }
    }
}
