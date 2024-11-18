<?php

declare(strict_types=1);

namespace Tests\Support\Documentation\Through;

use RecursionGuard\Recurser;

if (! function_exists('\\Tests\\Support\\Documentation\\Through\\through')) {
    function through(mixed $value, callable $through): mixed
    {
        return Recurser::call(fn () => $through($value), $value);
    }
}

if (! function_exists('\\Tests\\Support\\Documentation\\Through\\pipe')) {
    function pipe(mixed $value, callable ...$through): mixed
    {
        return array_reduce($through, through(...), $value);
    }
}
