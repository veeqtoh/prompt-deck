<?php

test('providers extend the base provider class')
    ->expect('Veeqtoh\PromptForge\Providers')
    ->classes()
    ->toExtend(\Illuminate\Support\ServiceProvider::class);