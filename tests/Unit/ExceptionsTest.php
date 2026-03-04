<?php

declare(strict_types=1);

use Veeqtoh\PromptDeck\Exceptions\ConfigurationException;
use Veeqtoh\PromptDeck\Exceptions\InvalidVersionException;
use Veeqtoh\PromptDeck\Exceptions\PROMPTDECKException;
use Veeqtoh\PromptDeck\Exceptions\PromptNotFoundException;
use Veeqtoh\PromptDeck\Exceptions\PromptRenderingException;

// --- Hierarchy ---

test('PROMPTDECKException extends base Exception', function () {
    expect(PROMPTDECKException::class)
        ->toExtend(\Exception::class);
});

test('ConfigurationException extends PROMPTDECKException', function () {
    $e = ConfigurationException::invalidPath('/some/path');

    expect($e)->toBeInstanceOf(PROMPTDECKException::class)
        ->and($e)->toBeInstanceOf(\Exception::class);
});

test('InvalidVersionException extends PROMPTDECKException', function () {
    $e = InvalidVersionException::forPrompt('test', 1);

    expect($e)->toBeInstanceOf(PROMPTDECKException::class);
});

test('PromptNotFoundException extends PROMPTDECKException', function () {
    $e = PromptNotFoundException::named('test');

    expect($e)->toBeInstanceOf(PROMPTDECKException::class);
});

test('PromptRenderingException extends PROMPTDECKException', function () {
    $e = PromptRenderingException::dueToMissingVariable('name', 'greeting');

    expect($e)->toBeInstanceOf(PROMPTDECKException::class);
});

// --- Message format ---

test('ConfigurationException::invalidPath() includes the path in the message', function () {
    $e = ConfigurationException::invalidPath('/invalid/path');

    expect($e->getMessage())
        ->toBe('Prompts path [/invalid/path] is not a directory or is not writable.');
});

test('InvalidVersionException::forPrompt() includes name and version in the message', function () {
    $e = InvalidVersionException::forPrompt('my-prompt', 5);

    expect($e->getMessage())
        ->toBe('Version 5 for prompt [my-prompt] does not exist.');
});

test('InvalidVersionException::noVersions() includes name in the message', function () {
    $e = InvalidVersionException::noVersions('empty-prompt');

    expect($e->getMessage())
        ->toBe('No versions found for prompt [empty-prompt].');
});

test('PromptNotFoundException::named() includes name in the message', function () {
    $e = PromptNotFoundException::named('missing-prompt');

    expect($e->getMessage())
        ->toBe('Prompt [missing-prompt] not found.');
});

test('PromptRenderingException::dueToMissingVariable() includes variable and prompt name', function () {
    $e = PromptRenderingException::dueToMissingVariable('username', 'login-prompt');

    expect($e->getMessage())
        ->toBe("Cannot render prompt [login-prompt]: missing required variable 'username'.");
});

// --- Edge cases ---

test('exception messages handle special characters in names', function () {
    $e = PromptNotFoundException::named('my/special prompt');

    expect($e->getMessage())
        ->toBe('Prompt [my/special prompt] not found.');
});

test('exception messages handle empty strings', function () {
    $e = PromptNotFoundException::named('');

    expect($e->getMessage())
        ->toBe('Prompt [] not found.');
});
