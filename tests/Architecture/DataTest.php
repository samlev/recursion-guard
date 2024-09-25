<?php

declare(strict_types=1);

arch('data classes are readonly')
    ->expect('RecursionGuard\Data')
    ->toBeClasses()
    ->toBeReadonly();

arch('data classes are makeable')
    ->expect('RecursionGuard\Data')
    ->toHaveMethod('make');
