<?php

test('globals are not used in any classes')
    ->expect(['dd', 'ddd', 'die', 'dump', 'ray', 'sleep'])
    ->toBeUsedInNothing();

test('all classes use strict types')
    ->expect('Veeqtoh\PromptDeck')
    ->toUseStrictTypes();

test('strict equality is enforced in all classes')
    ->expect('Veeqtoh\PromptDeck')
    ->toUseStrictEquality();

arch('PHP best practices are adhered to')->preset()->php();

arch('security best practices are adhered to')->preset()->security();

arch('Laravel best practices are adhered to')->preset()->laravel();
