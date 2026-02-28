<?php

use Illuminate\Database\Eloquent\Model;

test('models extends base model')
    ->expect('Veeqtoh\PromptForge\Models')
    ->classes()
    ->toExtend(Model::class);
