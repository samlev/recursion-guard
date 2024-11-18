<?php

declare(strict_types=1);

namespace Tests\Support\Documentation\BozoRepeat;

use RecursionGuard\Recurser;

if (! function_exists('\\Tests\\Support\\Documentation\\BozoRepeat\\bozo_repeat')) {
    function bozo_repeat(string $repeat = ''): string
    {
        return Recurser::call(
            // The callback that we want to call
            fn () => bozo_repeat() . ' : ' . bozo_repeat(),
            // What to return if this function is called recursively
            $repeat ?: 'bozo(' . random_int(0, 100) . ')'
        );
    }
}
