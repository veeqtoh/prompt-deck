<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

test('PromptDeck provider class extends base Laravel service provider class')
    ->expect('Veeqtoh\PromptDeck\Providers\PromptDeckServiceProvider')
    ->classes()
    ->toExtend(ServiceProvider::class);

test('PromptDeck facade class extends base Laravel facade class')
    ->expect('Veeqtoh\PromptDeck\Facades\PromptDeck')
    ->classes()
    ->toExtend(Facade::class);
