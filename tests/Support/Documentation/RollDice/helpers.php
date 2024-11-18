<?php

declare(strict_types=1);

namespace Tests\Support\Documentation\RollDice;

use RecursionGuard\Recurser;

if (! function_exists('\\Tests\\Support\\Documentation\\RollDice\\once')) {
    function once(callable $callback): mixed
    {
        return Recurser::call($callback, 'RECURSION');
    }
}

if (! function_exists('\\Tests\\Support\\Documentation\\RollDice\\twice')) {
    function twice(callable $callback): string
    {
        return once($callback) . ', ' . once($callback);
    }
}

if (! function_exists('\\Tests\\Support\\Documentation\\RollDice\\roll_dice')) {
    function roll_dice(): int
    {
        return random_int(1, 6);
    }
}

if (! function_exists('\\Tests\\Support\\Documentation\\RollDice\\roll_two_dice')) {
    function roll_two_dice(): string
    {
        return twice(roll_dice(...));
    }
}
