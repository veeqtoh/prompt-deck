<?php

use Illuminate\Database\Eloquent\Model;

test('models extends base model')
    ->expect('Veeqtoh\PromptDeck\Models')
    ->classes()
    ->toExtend(Model::class);
