<?php

declare(strict_types=1);

namespace Tests\Support\Documentation\Fib;

use RecursionGuard\Recurser;

if (! function_exists('\\Tests\\Support\\Documentation\\Fib\\fib')) {
    function fib(int $position): int
    {
        return Recurser::call(
            // Add the two previous numbers together
            fn () => fib($position - 1) + fib($position - 2),
            // Return 0 for negative positions, and 1 for position 0
            max(0, ($position ?: 1)),
            // Allow recursion until we hit position 0
            as: sprintf('fib(%d)', max(0, ($position ?: 1))),
        );
    }
}
