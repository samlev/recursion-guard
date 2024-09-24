<?php

arch('debug functions are not used')
    ->expect(['ray', 'dd', 'dump', 'ddd', 'exit', 'die', 'print_r', 'var_dump'])
    ->not->toBeUsed();

arch('files have strict types')
    ->expect('RecursionGuard')
    ->toUseStrictTypes();
