<?php

declare(strict_types=1);

use function Tests\Support\Documentation\Fib\fib;

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
