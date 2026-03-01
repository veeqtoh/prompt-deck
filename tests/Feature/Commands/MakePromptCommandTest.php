<?php

declare(strict_types=1);

test('make:prompt creates both user and system prompt files by default', function () {
    $this->artisan('make:prompt', ['name' => 'my-prompt'])
        ->expectsOutput('Prompt [my-prompt] created successfully at version 1.')
        ->assertSuccessful();

    expect(file_exists("{$this->tempDir}/my-prompt/v1/user.md"))->toBeTrue()
        ->and(file_exists("{$this->tempDir}/my-prompt/v1/system.md"))->toBeTrue()
        ->and(file_exists("{$this->tempDir}/my-prompt/metadata.json"))->toBeTrue();

    $meta = json_decode(file_get_contents("{$this->tempDir}/my-prompt/metadata.json"), true);
    expect($meta['name'])->toBe('my-prompt')
        ->and($meta)->toHaveKeys(['name', 'description', 'variables', 'created_at']);
});

test('make:prompt --no-system skips creating system prompt file', function () {
    $this->artisan('make:prompt', ['name' => 'user-only', '--no-system' => true])
        ->assertSuccessful();

    expect(file_exists("{$this->tempDir}/user-only/v1/user.md"))->toBeTrue()
        ->and(file_exists("{$this->tempDir}/user-only/v1/system.md"))->toBeFalse();
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

    // Both prompts should be replaced with default stub content.
    $userContent = file_get_contents("{$this->tempDir}/overwrite/v1/user.md");
    expect($userContent)->not->toBe('old user')
        ->and($userContent)->toContain('{{ $name }}');

    $systemContent = file_get_contents("{$this->tempDir}/overwrite/v1/system.md");
    expect($systemContent)->toContain('AI assistant');
});

test('make:prompt --from uses custom stub content', function () {
    $stubPath = "{$this->tempDir}/custom-stub.md";
    file_put_contents($stubPath, 'Custom stub content for {{ $topic }}');

    $this->artisan('make:prompt', ['name' => 'from-stub', '--from' => $stubPath])
        ->assertSuccessful();

    $content = file_get_contents("{$this->tempDir}/from-stub/v1/user.md");
    expect($content)->toBe('Custom stub content for {{ $topic }}');
});

test('make:prompt --from with non-existent stub falls back to default', function () {
    $this->artisan('make:prompt', ['name' => 'bad-stub', '--from' => '/nonexistent/stub.md'])
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

    expect(file_exists("{$newPath}/nested-prompt/v1/user.md"))->toBeTrue();
});

test('make:prompt respects config extension', function () {
    $this->app['config']->set('prompt-forge.extension', 'txt');

    $this->artisan('make:prompt', ['name' => 'txt-prompt'])
        ->assertSuccessful();

    expect(file_exists("{$this->tempDir}/txt-prompt/v1/user.txt"))->toBeTrue()
        ->and(file_exists("{$this->tempDir}/txt-prompt/v1/user.md"))->toBeFalse();
});

test('make:prompt default stub contains expected placeholders', function () {
    $this->artisan('make:prompt', ['name' => 'stub-check'])
        ->assertSuccessful();

    $content = file_get_contents("{$this->tempDir}/stub-check/v1/user.md");
    expect($content)->toContain('{{ $name }}')
        ->and($content)->toContain('{{ $input }}');
});

// =====================================================================
// Kebab-case conversion tests
// =====================================================================

test('make:prompt converts snake_case name to kebab-case', function () {
    $this->artisan('make:prompt', ['name' => 'my_cool_prompt'])
        ->expectsOutput('Prompt [my-cool-prompt] created successfully at version 1.')
        ->assertSuccessful();

    expect(file_exists("{$this->tempDir}/my-cool-prompt/v1/user.md"))->toBeTrue()
        ->and(file_exists("{$this->tempDir}/my_cool_prompt"))->toBeFalse();

    $meta = json_decode(file_get_contents("{$this->tempDir}/my-cool-prompt/metadata.json"), true);
    expect($meta['name'])->toBe('my-cool-prompt');
});

test('make:prompt converts PascalCase name to kebab-case', function () {
    $this->artisan('make:prompt', ['name' => 'MyPrompt'])
        ->expectsOutput('Prompt [my-prompt] created successfully at version 1.')
        ->assertSuccessful();

    expect(file_exists("{$this->tempDir}/my-prompt/v1/user.md"))->toBeTrue()
        ->and(file_exists("{$this->tempDir}/MyPrompt"))->toBeFalse();

    $meta = json_decode(file_get_contents("{$this->tempDir}/my-prompt/metadata.json"), true);
    expect($meta['name'])->toBe('my-prompt');
});

test('make:prompt converts camelCase name to kebab-case', function () {
    $this->artisan('make:prompt', ['name' => 'greetingMessage'])
        ->expectsOutput('Prompt [greeting-message] created successfully at version 1.')
        ->assertSuccessful();

    expect(file_exists("{$this->tempDir}/greeting-message/v1/user.md"))->toBeTrue();
});

test('make:prompt keeps already kebab-case name unchanged', function () {
    $this->artisan('make:prompt', ['name' => 'already-kebab'])
        ->expectsOutput('Prompt [already-kebab] created successfully at version 1.')
        ->assertSuccessful();

    expect(file_exists("{$this->tempDir}/already-kebab/v1/user.md"))->toBeTrue();
});

test('make:prompt converts UPPERCASE to lowercase kebab', function () {
    $this->artisan('make:prompt', ['name' => 'LOUD_PROMPT'])
        ->expectsOutput('Prompt [loud-prompt] created successfully at version 1.')
        ->assertSuccessful();

    expect(file_exists("{$this->tempDir}/loud-prompt/v1/user.md"))->toBeTrue();
});

test('make:prompt handles mixed separators', function () {
    $this->artisan('make:prompt', ['name' => 'My_Cool Prompt'])
        ->expectsOutput('Prompt [my-cool-prompt] created successfully at version 1.')
        ->assertSuccessful();

    expect(file_exists("{$this->tempDir}/my-cool-prompt/v1/user.md"))->toBeTrue();
});

// =====================================================================
// Stub file tests
// =====================================================================

test('make:prompt loads default user stub from package stubs directory', function () {
    $this->artisan('make:prompt', ['name' => 'stub-load'])
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
    file_put_contents("{$publishedDir}/user-prompt.stub", 'Published user stub for {{ $name }}');

    $this->artisan('make:prompt', ['name' => 'published-stub'])
        ->assertSuccessful();

    $content = file_get_contents("{$this->tempDir}/published-stub/v1/user.md");
    expect($content)->toBe('Published user stub for {{ $name }}');

    // Clean up.
    @unlink("{$publishedDir}/user-prompt.stub");
    @rmdir($publishedDir);
    @rmdir($this->app->basePath('stubs/prompt-forge'));
    @rmdir($this->app->basePath('stubs'));
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
