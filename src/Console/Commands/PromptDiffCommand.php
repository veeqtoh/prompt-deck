<?php

declare(strict_types=1);

namespace Veeqtoh\PromptForge\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Veeqtoh\PromptForge\Exceptions\PromptNotFoundException;

class PromptDiffCommand extends Command
{
    protected $signature = 'prompt:diff {name : The prompt name}
                              {--v1= : First version number}
                              {--v2= : Second version number}
                              {--type= : system, user, or all (default)}';

    protected $description = 'Show differences between two prompt versions';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle(): int
    {
        $name = $this->argument('name');
        $v1   = $this->option('v1');
        $v2   = $this->option('v2');
        $type = $this->option('type') ?? 'all';

        if (! $v1 || ! $v2) {
            $this->error('Both --v1 and --v2 are required.');

            return Command::FAILURE;
        }

        $basePath = config('promptforge.path').'/'.$name;

        if (! $this->files->isDirectory($basePath)) {
            throw PromptNotFoundException::named($name);
        }

        $ext = config('promptforge.extension', 'md');

        $filesToCompare = [];
        if ($type === 'system' || $type === 'all') {
            $filesToCompare[] = ['system.'.$ext, 'System Prompt'];
        }
        if ($type === 'user' || $type === 'all') {
            $filesToCompare[] = ['user.'.$ext, 'User Prompt'];
        }

        foreach ($filesToCompare as [$file, $label]) {
            $path1 = $basePath.'/v'.$v1.'/'.$file;
            $path2 = $basePath.'/v'.$v2.'/'.$file;

            if (! $this->files->exists($path1) && ! $this->files->exists($path2)) {
                continue;
            }

            $this->info("\n--- {$label} ---");

            $content1 = $this->files->exists($path1) ? $this->files->get($path1) : '';
            $content2 = $this->files->exists($path2) ? $this->files->get($path2) : '';

            $this->diff($content1, $content2);
        }

        return Command::SUCCESS;
    }

    protected function diff(string $old, string $new): void
    {
        // Simple line-by-line diff (you could use a package like sebastian/diff)
        $oldLines = explode("\n", $old);
        $newLines = explode("\n", $new);

        $diff     = new \Diff($oldLines, $newLines);
        $renderer = new \Diff_Renderer_Text_Unified;
        echo $diff->render($renderer);
    }
}
