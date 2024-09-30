<?php

declare(strict_types=1);

/*
 * @dataset [
 *   array[]: $from,
 *   string: $signature,
 * ]
 */
dataset('contexts', [
    'none' => [[], ':0'],
    'file' => [['file' => 'foo.php'], 'foo.php:0'],
    'function' => [['function' => 'foo'], ':foo'],
    'class' => [['class' => 'foo'], ':foo@0'],
    'line' => [['line' => 42], ':42'],
    'object' => [['object' => (object) []], ':0'],
    'signature' => [['signature' => 'foo'], 'foo'],
    'classless function' => [
        ['file' => 'foo.php', 'line' => 42, 'function' => 'foo', 'object' => (object) []],
        'foo.php:foo',
    ],
    'anonymous function' => [
        ['file' => 'foo.php', 'line' => 42, 'object' => (object) []],
        'foo.php:42',
    ],
    'all except signature' => [
        ['file' => 'foo.php', 'line' => 42, 'function' => 'foo', 'class' => 'foo', 'object' => (object) []],
        'foo.php:foo@foo',
    ],
    'all with signature' => [
        [
            'file' => 'foo.php',
            'line' => 42,
            'function' => 'foo',
            'class' => 'foo',
            'object' => (object) [],
            'signature' => 'foo',
        ],
        'foo',
    ],
]);
