<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

test('PROMPTDECK provider class extends base Laravel service provider class')
    ->expect('Veeqtoh\PromptDeck\Providers\PROMPTDECKProvider')
    ->classes()
    ->toExtend(ServiceProvider::class);

test('PROMPTDECK facade class extends base Laravel facade class')
    ->expect('Veeqtoh\PromptDeck\Facades\PROMPTDECK')
    ->classes()
    ->toExtend(Facade::class);
