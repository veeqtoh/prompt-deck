<?php

declare(strict_types=1);

test('make:prompt creates only a system prompt file by default', function () {
    $this->artisan('make:prompt', ['name' => 'my-prompt'])
        ->expectsOutput('Prompt [my-prompt] created successfully at version 1.')
        ->assertSuccessful();

    expect(file_exists("{$this->tempDir}/my-prompt/v1/system.md"))->toBeTrue()
        ->and(file_exists("{$this->tempDir}/my-prompt/v1/user.md"))->toBeFalse()
        ->and(file_exists("{$this->tempDir}/my-prompt/metadata.json"))->toBeTrue();

    $meta = json_decode(file_get_contents("{$this->tempDir}/my-prompt/metadata.json"), true);
    expect($meta['name'])->toBe('my-prompt')
        ->and($meta)->toHaveKeys(['name', 'description', 'roles', 'variables', 'created_at'])
        ->and($meta['roles'])->toBe(['system']);
});

test('make:prompt --user also creates a user prompt file', function () {
    $this->artisan('make:prompt', ['name' => 'with-user', '--user' => true])
        ->assertSuccessful();

    expect(file_exists("{$this->tempDir}/with-user/v1/system.md"))->toBeTrue()
        ->and(file_exists("{$this->tempDir}/with-user/v1/user.md"))->toBeTrue();

    $meta = json_decode(file_get_contents("{$this->tempDir}/with-user/metadata.json"), true);
    expect($meta['roles'])->toBe(['system', 'user']);
});

test('make:prompt -u also creates a user prompt file', function () {
    $this->artisan('make:prompt', ['name' => 'shorthand-user', '-u' => true])
        ->assertSuccessful();

    expect(file_exists("{$this->tempDir}/shorthand-user/v1/system.md"))->toBeTrue()
        ->and(file_exists("{$this->tempDir}/shorthand-user/v1/user.md"))->toBeTrue();
});

test('make:prompt fails when prompt exists without --force', function () {
    $this->createPromptFixture('existing', 1, 'sys', 'usr');

    $this->artisan('make:prompt', ['name' => 'existing'])
        ->expectsOutput('Prompt [existing] already exists. Use --force to overwrite.')
        ->assertFailed();
});

test('make:prompt --force overwrites existing prompt', function () {
    $this->createPromptFixture('overwrite', 1, 'old system', 'old user');

    $this->artisan('make:prompt', ['name' => 'overwrite', '--force' => true])
        ->assertSuccessful();

    // System prompt should be replaced with default stub content.
    $systemContent = file_get_contents("{$this->tempDir}/overwrite/v1/system.md");
    expect($systemContent)->not->toBe('old system')
        ->and($systemContent)->toContain('AI assistant');
});

test('make:prompt -f overwrites existing prompt', function () {
    $this->createPromptFixture('overwrite', 1, 'old system', 'old user');

    $this->artisan('make:prompt', ['name' => 'overwrite', '-f' => true])
        ->assertSuccessful();

    $systemContent = file_get_contents("{$this->tempDir}/overwrite/v1/system.md");
    expect($systemContent)->not->toBe('old system')
        ->and($systemContent)->toContain('AI assistant');
});

test('make:prompt --from uses custom stub for user prompt', function () {
    $stubPath = "{$this->tempDir}/custom-stub.md";
    file_put_contents($stubPath, 'Custom stub content for {{ $topic }}');

    $this->artisan('make:prompt', ['name' => 'from-stub', '--from' => $stubPath, '--user' => true])
        ->assertSuccessful();

    $content = file_get_contents("{$this->tempDir}/from-stub/v1/user.md");
    expect($content)->toBe('Custom stub content for {{ $topic }}');
});

test('make:prompt --from with non-existent stub falls back to default', function () {
    $this->artisan('make:prompt', ['name' => 'bad-stub', '--from' => '/nonexistent/stub.md', '--user' => true])
        ->assertSuccessful();

    $content = file_get_contents("{$this->tempDir}/bad-stub/v1/user.md");
    expect($content)->toContain('{{ $name }}');
});

test('make:prompt creates base directory if it does not exist', function () {
    $newPath = "{$this->tempDir}/nested/prompts";
    $this->app['config']->set('prompt-forge.path', $newPath);

    // Re-register the singleton with the new path.
    $this->app->forgetInstance(\Veeqtoh\PromptForge\PromptManager::class);

    $this->artisan('make:prompt', ['name' => 'nested-prompt'])
        ->assertSuccessful();

    expect(file_exists("{$newPath}/nested-prompt/v1/system.md"))->toBeTrue();
});

test('make:prompt respects config extension', function () {
    $this->app['config']->set('prompt-forge.extension', 'txt');

    $this->artisan('make:prompt', ['name' => 'txt-prompt'])
        ->assertSuccessful();

    expect(file_exists("{$this->tempDir}/txt-prompt/v1/system.txt"))->toBeTrue()
        ->and(file_exists("{$this->tempDir}/txt-prompt/v1/system.md"))->toBeFalse();
});

test('make:prompt default system stub contains expected content', function () {
    $this->artisan('make:prompt', ['name' => 'stub-check'])
        ->assertSuccessful();

    $content = file_get_contents("{$this->tempDir}/stub-check/v1/system.md");
    expect($content)->toContain('AI assistant')
        ->and($content)->toContain('{{ $tone }}');
});

// =====================================================================
// Kebab-case conversion tests
// =====================================================================

test('make:prompt converts snake_case name to kebab-case', function () {
    $this->artisan('make:prompt', ['name' => 'my_cool_prompt'])
        ->expectsOutput('Prompt [my-cool-prompt] created successfully at version 1.')
        ->assertSuccessful();

    expect(file_exists("{$this->tempDir}/my-cool-prompt/v1/system.md"))->toBeTrue()
        ->and(file_exists("{$this->tempDir}/my_cool_prompt"))->toBeFalse();

    $meta = json_decode(file_get_contents("{$this->tempDir}/my-cool-prompt/metadata.json"), true);
    expect($meta['name'])->toBe('my-cool-prompt');
});

test('make:prompt converts PascalCase name to kebab-case', function () {
    $this->artisan('make:prompt', ['name' => 'MyPrompt'])
        ->expectsOutput('Prompt [my-prompt] created successfully at version 1.')
        ->assertSuccessful();

    expect(file_exists("{$this->tempDir}/my-prompt/v1/system.md"))->toBeTrue()
        ->and(file_exists("{$this->tempDir}/MyPrompt"))->toBeFalse();

    $meta = json_decode(file_get_contents("{$this->tempDir}/my-prompt/metadata.json"), true);
    expect($meta['name'])->toBe('my-prompt');
});

test('make:prompt converts camelCase name to kebab-case', function () {
    $this->artisan('make:prompt', ['name' => 'greetingMessage'])
        ->expectsOutput('Prompt [greeting-message] created successfully at version 1.')
        ->assertSuccessful();

    expect(file_exists("{$this->tempDir}/greeting-message/v1/system.md"))->toBeTrue();
});

test('make:prompt keeps already kebab-case name unchanged', function () {
    $this->artisan('make:prompt', ['name' => 'already-kebab'])
        ->expectsOutput('Prompt [already-kebab] created successfully at version 1.')
        ->assertSuccessful();

    expect(file_exists("{$this->tempDir}/already-kebab/v1/system.md"))->toBeTrue();
});

test('make:prompt converts UPPERCASE to lowercase kebab', function () {
    $this->artisan('make:prompt', ['name' => 'LOUD_PROMPT'])
        ->expectsOutput('Prompt [loud-prompt] created successfully at version 1.')
        ->assertSuccessful();

    expect(file_exists("{$this->tempDir}/loud-prompt/v1/system.md"))->toBeTrue();
});

test('make:prompt handles mixed separators', function () {
    $this->artisan('make:prompt', ['name' => 'My_Cool Prompt'])
        ->expectsOutput('Prompt [my-cool-prompt] created successfully at version 1.')
        ->assertSuccessful();

    expect(file_exists("{$this->tempDir}/my-cool-prompt/v1/system.md"))->toBeTrue();
});

// =====================================================================
// Stub file tests
// =====================================================================

test('make:prompt loads default user stub from package stubs directory', function () {
    $this->artisan('make:prompt', ['name' => 'stub-load', '--user' => true])
        ->assertSuccessful();

    $content  = file_get_contents("{$this->tempDir}/stub-load/v1/user.md");
    $stubFile = realpath(__DIR__.'/../../../stubs/user-prompt.stub');

    expect($stubFile)->not->toBeFalse('user-prompt.stub should exist in package stubs/')
        ->and($content)->toBe(file_get_contents($stubFile));
});

test('make:prompt loads default system stub from package stubs directory', function () {
    $this->artisan('make:prompt', ['name' => 'sys-stub-load'])
        ->assertSuccessful();

    $content  = file_get_contents("{$this->tempDir}/sys-stub-load/v1/system.md");
    $stubFile = realpath(__DIR__.'/../../../stubs/system-prompt.stub');

    expect($stubFile)->not->toBeFalse('system-prompt.stub should exist in package stubs/')
        ->and($content)->toBe(file_get_contents($stubFile));
});

test('make:prompt prefers published stubs over package defaults', function () {
    // Simulate a published stub in the app's stubs/prompt-forge/ directory.
    $publishedDir = $this->app->basePath('stubs/prompt-forge');
    @mkdir($publishedDir, 0755, true);
    file_put_contents("{$publishedDir}/system-prompt.stub", 'Published system stub for {{ $name }}');

    try {
        $this->artisan('make:prompt', ['name' => 'published-stub'])
            ->assertSuccessful();

        $content = file_get_contents("{$this->tempDir}/published-stub/v1/system.md");
        expect($content)->toBe('Published system stub for {{ $name }}');
    } finally {
        // Always clean up, even if assertions fail.
        $this->deleteDirectory($this->app->basePath('stubs'));
    }
});

test('make:prompt system stub contains expected AI assistant content', function () {
    $stubFile = realpath(__DIR__.'/../../../stubs/system-prompt.stub');
    $content  = file_get_contents($stubFile);

    expect($content)->toContain('AI assistant')
        ->and($content)->toContain('{{ $tone }}');
});

test('make:prompt user stub contains expected placeholders', function () {
    $stubFile = realpath(__DIR__.'/../../../stubs/user-prompt.stub');
    $content  = file_get_contents($stubFile);

    expect($content)->toContain('{{ $name }}')
        ->and($content)->toContain('{{ $input }}');
});

// =====================================================================
// Extra role tests (--role option)
// =====================================================================

test('make:prompt --role creates additional role prompt files', function () {
    $this->artisan('make:prompt', [
        'name'   => 'multi-role',
        '--role' => ['assistant', 'developer'],
    ])->assertSuccessful();

    expect(file_exists("{$this->tempDir}/multi-role/v1/system.md"))->toBeTrue()
        ->and(file_exists("{$this->tempDir}/multi-role/v1/user.md"))->toBeFalse()
        ->and(file_exists("{$this->tempDir}/multi-role/v1/assistant.md"))->toBeTrue()
        ->and(file_exists("{$this->tempDir}/multi-role/v1/developer.md"))->toBeTrue();
});

test('make:prompt --role replaces {{ $role }} placeholder in generated file', function () {
    $this->artisan('make:prompt', [
        'name'   => 'role-content',
        '--role' => ['assistant'],
    ])->assertSuccessful();

    $content = file_get_contents("{$this->tempDir}/role-content/v1/assistant.md");
    expect($content)->toContain('assistant')
        ->and($content)->not->toContain('{{ $role }}');
});

test('make:prompt --role converts role names to kebab-case', function () {
    $this->artisan('make:prompt', [
        'name'   => 'kebab-roles',
        '--role' => ['ToolCall'],
    ])->assertSuccessful();

    expect(file_exists("{$this->tempDir}/kebab-roles/v1/tool-call.md"))->toBeTrue()
        ->and(file_exists("{$this->tempDir}/kebab-roles/v1/ToolCall.md"))->toBeFalse();
});

test('make:prompt --role records all roles in metadata', function () {
    $this->artisan('make:prompt', [
        'name'   => 'meta-roles',
        '--role' => ['assistant', 'tool'],
    ])->assertSuccessful();

    $meta = json_decode(file_get_contents("{$this->tempDir}/meta-roles/metadata.json"), true);
    expect($meta['roles'])->toBe(['system', 'assistant', 'tool']);
});

test('make:prompt --user with --role records system, user and extra roles in metadata', function () {
    $this->artisan('make:prompt', [
        'name'   => 'all-roles',
        '--user' => true,
        '--role' => ['assistant'],
    ])->assertSuccessful();

    $meta = json_decode(file_get_contents("{$this->tempDir}/all-roles/metadata.json"), true);
    expect($meta['roles'])->toBe(['system', 'user', 'assistant']);
});

test('make:prompt without --role creates no extra role files', function () {
    $this->artisan('make:prompt', ['name' => 'no-extras'])
        ->assertSuccessful();

    $files     = glob("{$this->tempDir}/no-extras/v1/*.md");
    $filenames = array_map('basename', $files);
    sort($filenames);

    expect($filenames)->toBe(['system.md']);
});

test('make:prompt --role respects config extension for role files', function () {
    $this->app['config']->set('prompt-forge.extension', 'txt');

    $this->artisan('make:prompt', [
        'name'   => 'ext-role',
        '--role' => ['assistant'],
    ])->assertSuccessful();

    expect(file_exists("{$this->tempDir}/ext-role/v1/assistant.txt"))->toBeTrue()
        ->and(file_exists("{$this->tempDir}/ext-role/v1/assistant.md"))->toBeFalse();
});

test('make:prompt --role with --force overwrites role files', function () {
    // Create initial prompt with an assistant role.
    $this->artisan('make:prompt', [
        'name'   => 'force-roles',
        '--role' => ['assistant'],
    ])->assertSuccessful();

    $original = file_get_contents("{$this->tempDir}/force-roles/v1/assistant.md");

    // Overwrite with a different extra role via --force.
    $this->artisan('make:prompt', [
        'name'    => 'force-roles',
        '--role'  => ['developer'],
        '--force' => true,
    ])->assertSuccessful();

    expect(file_exists("{$this->tempDir}/force-roles/v1/developer.md"))->toBeTrue();
});

test('make:prompt role stub file exists in package stubs directory', function () {
    $stubFile = realpath(__DIR__.'/../../../stubs/role-prompt.stub');

    expect($stubFile)->not->toBeFalse('role-prompt.stub should exist in package stubs/')
        ->and(file_get_contents($stubFile))->toContain('{{ $role }}');
});

test('make:prompt prefers published role stub over package default', function () {
    $publishedDir = $this->app->basePath('stubs/prompt-forge');
    @mkdir($publishedDir, 0755, true);
    file_put_contents("{$publishedDir}/role-prompt.stub", 'Custom {{ $role }} stub');

    try {
        $this->artisan('make:prompt', [
            'name'   => 'pub-role-stub',
            '--role' => ['assistant'],
        ])->assertSuccessful();

        $content = file_get_contents("{$this->tempDir}/pub-role-stub/v1/assistant.md");
        expect($content)->toBe('Custom assistant stub');
    } finally {
        $this->deleteDirectory($this->app->basePath('stubs'));
    }
});

// =====================================================================
// Interactive mode tests (--interactive / -i)
// =====================================================================

test('make:prompt -i prompts for additional roles interactively', function () {
    $this->artisan('make:prompt', ['name' => 'interactive-test', '--interactive' => true])
        ->expectsQuestion(
            'Any additional roles to create prompt files for? (comma-separated, e.g. assistant,developer — press Enter to skip)',
            'assistant, tool'
        )
        ->assertSuccessful();

    expect(file_exists("{$this->tempDir}/interactive-test/v1/assistant.md"))->toBeTrue()
        ->and(file_exists("{$this->tempDir}/interactive-test/v1/tool.md"))->toBeTrue()
        ->and(file_exists("{$this->tempDir}/interactive-test/v1/system.md"))->toBeTrue()
        ->and(file_exists("{$this->tempDir}/interactive-test/v1/user.md"))->toBeFalse();
});

test('make:prompt -i with empty answer creates no extra roles', function () {
    $this->artisan('make:prompt', ['name' => 'interactive-skip', '-i' => true])
        ->expectsQuestion(
            'Any additional roles to create prompt files for? (comma-separated, e.g. assistant,developer — press Enter to skip)',
            ''
        )
        ->assertSuccessful();

    $files     = glob("{$this->tempDir}/interactive-skip/v1/*.md");
    $filenames = array_map('basename', $files);
    sort($filenames);

    expect($filenames)->toBe(['system.md']);
});

test('make:prompt --role takes precedence over interactive prompt', function () {
    // When --role is specified, the interactive prompt should NOT fire.
    $this->artisan('make:prompt', [
        'name'   => 'role-no-ask',
        '--role' => ['developer'],
    ])->assertSuccessful();

    expect(file_exists("{$this->tempDir}/role-no-ask/v1/developer.md"))->toBeTrue();
});
