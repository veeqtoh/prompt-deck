<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

test('PromptForge provider class extends base Laravel service provider class')
    ->expect('Veeqtoh\PromptForge\Providers\PromptForgeProvider')
    ->classes()
    ->toExtend(ServiceProvider::class);

test('PromptForge facade class extends base Laravel facade class')
    ->expect('Veeqtoh\PromptForge\Facades\PromptForge')
    ->classes()
    ->toExtend(Facade::class);