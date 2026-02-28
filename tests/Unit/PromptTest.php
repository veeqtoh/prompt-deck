<?php

declare(strict_types=1);

use Veeqtoh\PromptForge\Prompt;

// --- Accessor methods ---

test('name() returns the constructor name', function () {
    $prompt = new Prompt('greeting', 1, 'system text', 'user text');

    expect($prompt->name())->toBe('greeting');
});

test('version() returns the constructor version', function () {
    $prompt = new Prompt('greeting', 3, 'system text', 'user text');

    expect($prompt->version())->toBe(3);
});

test('metadata() returns the constructor metadata', function () {
    $meta   = ['description' => 'A test prompt', 'author' => 'tester'];
    $prompt = new Prompt('greeting', 1, 'sys', 'usr', $meta);

    expect($prompt->metadata())->toBe($meta);
});

test('metadata() defaults to empty array when not provided', function () {
    $prompt = new Prompt('greeting', 1, 'sys', 'usr');

    expect($prompt->metadata())->toBe([]);
});

// --- toArray ---

test('toArray() returns all 5 keys with correct values', function () {
    $meta   = ['description' => 'test'];
    $prompt = new Prompt('chat', 2, 'system content', 'user content', $meta);

    expect($prompt->toArray())->toBe([
        'name'     => 'chat',
        'version'  => 2,
        'system'   => 'system content',
        'user'     => 'user content',
        'metadata' => $meta,
    ]);
});

test('toArray() round-trips correctly via constructor', function () {
    $prompt = new Prompt('roundtrip', 5, 'sys prompt', 'usr prompt', ['key' => 'val']);
    $array  = $prompt->toArray();

    $reconstructed = new Prompt(
        $array['name'],
        $array['version'],
        $array['system'],
        $array['user'],
        $array['metadata'],
    );

    expect($reconstructed->toArray())->toBe($prompt->toArray());
});

// --- renderSystem ---

test('renderSystem() with no variables returns raw content', function () {
    $prompt = new Prompt('test', 1, 'Hello {{ $name }}', 'user text');

    expect($prompt->renderSystem())->toBe('Hello {{ $name }}');
});

test('renderSystem() replaces {{ $var }} spaced syntax', function () {
    $prompt = new Prompt('test', 1, 'Hello {{ $name }}, welcome!', 'user');

    expect($prompt->renderSystem(['name' => 'Alice']))->toBe('Hello Alice, welcome!');
});

test('renderSystem() replaces {{var}} non-spaced syntax', function () {
    $prompt = new Prompt('test', 1, 'Hello {{name}}, welcome!', 'user');

    expect($prompt->renderSystem(['name' => 'Bob']))->toBe('Hello Bob, welcome!');
});

// --- renderUser ---

test('renderUser() replaces variables correctly', function () {
    $prompt = new Prompt('test', 1, 'system', 'Input: {{ $input }}');

    expect($prompt->renderUser(['input' => 'test data']))->toBe('Input: test data');
});

// --- Interpolation edge cases ---

test('interpolation handles both syntaxes in the same string', function () {
    $prompt = new Prompt('test', 1, '{{ $greeting }} and {{name}}!', 'user');

    expect($prompt->renderSystem(['greeting' => 'Hi', 'name' => 'World']))
        ->toBe('Hi and World!');
});

test('interpolation leaves unreplaced placeholders intact (missing variables)', function () {
    $prompt = new Prompt('test', 1, 'Hello {{ $name }}, you are {{ $role }}', 'user');

    expect($prompt->renderSystem(['name' => 'Alice']))
        ->toBe('Hello Alice, you are {{ $role }}');
});

test('interpolation handles special characters in values', function () {
    $prompt = new Prompt('test', 1, 'Content: {{ $html }}', 'user');

    expect($prompt->renderSystem(['html' => '<script>alert("xss")</script>']))
        ->toBe('Content: <script>alert("xss")</script>');
});

test('interpolation handles dollar sign in values', function () {
    $prompt = new Prompt('test', 1, 'Price: {{ $price }}', 'user');

    expect($prompt->renderSystem(['price' => '$100.00']))
        ->toBe('Price: $100.00');
});

test('interpolation handles empty string value', function () {
    $prompt = new Prompt('test', 1, 'Value: [{{ $val }}]', 'user');

    expect($prompt->renderSystem(['val' => '']))->toBe('Value: []');
});

test('interpolation handles numeric value via string cast', function () {
    $prompt = new Prompt('test', 1, 'Count: {{ $count }}', 'user');

    expect($prompt->renderSystem(['count' => 42]))->toBe('Count: 42');
});

test('interpolation handles float value', function () {
    $prompt = new Prompt('test', 1, 'Score: {{ $score }}', 'user');

    expect($prompt->renderSystem(['score' => 3.14]))->toBe('Score: 3.14');
});

test('interpolation replaces multiple occurrences of the same variable', function () {
    $prompt = new Prompt('test', 1, '{{ $name }} said hello to {{ $name }}', 'user');

    expect($prompt->renderSystem(['name' => 'Alice']))
        ->toBe('Alice said hello to Alice');
});

test('interpolation handles multiple different variables', function () {
    $prompt = new Prompt('test', 1, '{{ $a }} {{ $b }} {{ $c }}', 'user');

    expect($prompt->renderSystem(['a' => 'X', 'b' => 'Y', 'c' => 'Z']))
        ->toBe('X Y Z');
});

test('interpolation handles multiline content', function () {
    $content = "Line 1: {{ \$name }}\nLine 2: {{ \$role }}\nLine 3: done";
    $prompt  = new Prompt('test', 1, $content, 'user');

    expect($prompt->renderSystem(['name' => 'Alice', 'role' => 'admin']))
        ->toBe("Line 1: Alice\nLine 2: admin\nLine 3: done");
});

// --- Arrayable contract ---

test('Prompt implements Arrayable', function () {
    $prompt = new Prompt('test', 1, 'sys', 'usr');

    expect($prompt)->toBeInstanceOf(\Illuminate\Contracts\Support\Arrayable::class);
});
