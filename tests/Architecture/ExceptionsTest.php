<?php

declare(strict_types=1);

arch('exceptions extend the base exception')
    ->expect('RecursionGuard\Exceptions')
    ->toBeClasses()
    ->toExtend('\Exception')
    ->not->toBeAbstract();

arch('exceptions have the correct suffix')
    ->expect('RecursionGuard\Exceptions')
    ->toHaveSuffix('Exception');

arch('exceptions are make-able')
    ->expect('RecursionGuard\Exceptions')
    ->toHaveMethod('make');
