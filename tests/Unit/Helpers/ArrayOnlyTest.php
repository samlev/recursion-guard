<?php

declare(strict_types=1);

use function RecursionGuard\array_only;

covers('RecursionGuard\\array_only');

it('returns only selected keys', function ($keys, $expected) {
    $input = [
        1,
        2,
        3,
        4,
        5,
        'one' => 'one',
        'two' => 'two',
        'three' => 'three',
        'four' => 'four',
        'five' => 'five',
    ];

    expect(array_only($input, $keys))->toBe($expected);
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

it('handles duplicate keys', function () {
    expect(array_only(
        ['one' => 'one', 'two' => 'two', 'three' => 'three', 'four' => 'four', 'five' => 'five'],
        ['two', 'two', 'three', 'three', 'four', 'four']
    ))->toBe([
        'two' => 'two',
        'three' => 'three',
        'four' => 'four',
    ]);
});

it('ignores keys for keys array', function () {
    expect(array_only(['foo' => 'bar', 'bar' => 'foo'], ['foo' => 'bar']))
        ->toBe(['bar' => 'foo']);
});

it('ignores invalid key types', function () {
    array_only([], [[], true, null, (object) [], 3.14]);
})->throwsNoExceptions();
