<?php

use function RecursionGuard\array_only;

covers('RecursionGuard\\array_only');

it('returns only selected keys', function($keys, $expected) {
    $input = [
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
        [
           'one',
           'two',
           'three',
           'four',
           'five',
        ],
        [
           'one' => 'one',
           'two' => 'two',
           'three' => 'three',
           'four' => 'four',
           'five' => 'five',
        ],
    ],
    'first key' => [
        [
           'one',
        ],
        [
           'one' => 'one',
        ],
    ],
    'last key' => [
        [
           'five',
        ],
        [
           'five' => 'five',
        ],
    ],
    'middle keys' => [
        [
           'two',
           'three',
           'four',
        ],
        [
           'two' => 'two',
           'three' => 'three',
           'four' => 'four',
        ],
    ],
    'keys that do not exist' => [
        ['six', 'seven', 'eight'],
        [],
    ],
    'keys out of order' => [
        [
           'two',
           'four',
           'one',
        ],
        [
           'one' => 'one',
           'two' => 'two',
           'four' => 'four',
        ],
    ],
]);

