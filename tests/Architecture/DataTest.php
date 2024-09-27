<?php

declare(strict_types=1);

arch('data classes are readonly')
    ->expect('RecursionGuard\Data')
    ->toBeClasses()
    ->toBeReadonly();
