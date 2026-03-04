<?php

test('providers extend the base provider class')
    ->expect('Veeqtoh\PromptDeck\Providers')
    ->classes()
    ->toExtend(\Illuminate\Support\ServiceProvider::class);
