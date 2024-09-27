<?php

function fib(int $position): int
{
    return RecursionGuard\Recurser::call(
        // Add the two previous numbers together
        fn () => fib($position - 1) + fib($position - 2),
        // Return 0 for negative positions, and 1 for position 0
        max(0, ($position ?: 1)),
        // Allow recursion until we hit position 0
        as: sprintf('fib(%d)', max(0, ($position ?: 1))),
    );
}

it('provides the expected fibonacci number', function ($for, $expected) {
    expect(fib($for))->toBe($expected);
})->with([
    'zero' => [0, 0],
    'one' => [1, 1],
    'two' => [2, 1],
    'three' => [3, 2],
    'four' => [4, 3],
    'five' => [5, 5],
    'six' => [6, 8],
    'seven' => [7, 13],
    'eight' => [8, 21],
    'nine' => [9, 34],
    'ten' => [10, 55],
    'negative' => [random_int(PHP_INT_MIN, -1), 0],
]);
