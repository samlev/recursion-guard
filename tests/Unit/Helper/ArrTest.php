<?php

declare(strict_types=1);

use RecursionGuard\Helper\Arr;

covers(Arr::class);


it('only returns selected keys', function ($keys, $expected) {
    $input = [1, 2, 3, 4, 5, 'one' => 'one', 'two' => 'two', 'three' => 'three', 'four' => 'four', 'five' => 'five'];

    expect(Arr::only($input, $keys))->toBe($expected);
})->with([
    'no keys' => [[], []],
    'all keys' => [
        [0, 1, 2, 3, 4, 'one', 'two', 'three', 'four', 'five'],
        [
            0 => 1,
            1 => 2,
            2 => 3,
            3 => 4,
            4 => 5,
            'one' => 'one',
            'two' => 'two',
            'three' => 'three',
            'four' => 'four',
            'five' => 'five',
        ],
    ],
    'first key' => [
        [0],
        [0 => 1],
    ],
    'last key' => [
        ['five'],
        ['five' => 'five'],
    ],
    'middle keys' => [
        [1, 2, 3, 4, 'one', 'two', 'three', 'four'],
        [
            1 => 2,
            2 => 3,
            3 => 4,
            4 => 5,
            'one' => 'one',
            'two' => 'two',
            'three' => 'three',
            'four' => 'four',
        ],
    ],
    'keys that do not exist' => [
        ['six', 'seven', 'eight', 9, 5, 100],
        [],
    ],
    'keys out of order' => [
        ['two', 'four', 'one', 3, 1],
        [
            1 => 2,
            3 => 4,
            'one' => 'one',
            'two' => 'two',
            'four' => 'four',
        ],
    ],
]);

it('only handles duplicate keys', function () {
    expect(Arr::only(
        ['one' => 'one', 'two' => 'two', 'three' => 'three', 'four' => 'four', 'five' => 'five'],
        ['two', 'two', 'three', 'three', 'four', 'four']
    ))->toBe([
        'two' => 'two',
        'three' => 'three',
        'four' => 'four',
    ]);
});

it('only ignores keys for keys array', function () {
    expect(Arr::only(['foo' => 'bar', 'bar' => 'foo'], ['foo' => 'bar']))
        ->toBe(['bar' => 'foo']);
});

it('only ignores invalid key types', function () {
    Arr::only([], [[], true, null, (object) [], 3.14]);
})->throwsNoExceptions();
