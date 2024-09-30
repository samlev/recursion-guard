<?php

declare(strict_types=1);

use RecursionGuard\Data\Frame;

/*
 * @dataset [
 *   array[]: $from,
 *   bool: $empty,
 * ]
 */
dataset('frames', [
    'none' => [[], true],
    'file' => [['file' => 'foo.php'], false],
    'line' => [['line' => 42], false],
    'function' => [['function' => 'foo'], false],
    'class' => [['class' => 'foo'], false],
    'object' => [['object' => (object)[]], false],
    'all' => [
        ['file' => 'foo.php', 'line' => 42, 'function' => 'foo', 'class' => 'foo', 'object' => (object)[]],
        false
    ],
    'differently ordered keys' => [
        ['object' => (object)[], 'file' => 'foo.php', 'function' => 'foo', 'line' => 42, 'class' => 'foo'],
        false
    ],
    'empty values' => [['file' => '', 'line' => 0, 'function' => '', 'class' => '', 'object' => null], true],
    'integer keys' => [
        [
            0 => 'foo.php',
            1 => 42,
            2 => 'foo',
            3 => 'foo',
            4 => (object)[],
        ],
        true,
    ],
    'type' => [['type' => '->'], true],
    'unknown' => [['unknown' => 'foo'], true],
    'full trace frame' => [
        [
            'file' => 'foo.php',
            'line' => 42,
            'class' => 'foo',
            'function' => 'foo',
            'object' => (object)[],
            'type' => '->',
            'args' => [],
        ],
        false
    ],
]);

/*
 * @dataset [
 *   Frame: $from,
 *   bool: $empty,
 * ]
 */
dataset('frame objects', [
    'empty' => [new Frame(), true],
    'file' => [new Frame('foo.php'), false],
    'class' => [new Frame(class: 'foo'), false],
    'function' => [new Frame(function: 'foo'), false],
    'line' => [new Frame(line: 42), false],
    'object' => [new Frame(object: (object)[]), false],
    'all' => [
        new Frame('foo.php', 'foo', 'foo', 42, (object)[]),
        false
    ],
    'file function and object' => [
        new Frame('foo.php', function: 'foo', object: (object)[]),
        false
    ],
]);

/*
 * @dataset [
 *   Frame[]: $from,
 *   bool: $empty,
 * ]
 */
dataset('frame arrays', [
    'empty' => [[], true],
    'one frame' => [
        [new Frame('foo.php', 'foo', 'foo', 42, (object) [])],
        false,
    ],
    'two frames' => [
        [
            new Frame('foo.php', 'foo', 'foo', 42, (object) []),
            new Frame('bing.php', 'bang', 'boom', 99, new Frame()),
        ],
        false,
    ],
    'three frames' => [
        [
            new Frame('foo.php', 'foo', 'foo', 42, (object) []),
            new Frame('bing.php', 'bang', 'boom', 99, new Frame()),
            new Frame('whizz.php', line: 24),
        ],
        false,
    ],
    'empty frames' => [
        [
            new Frame(),
            new Frame(),
            new Frame(),
        ],
        true,
    ],
    'mixed frames' => [
        [
            new Frame('foo.php', 'foo', 'foo', 42, (object) []),
            new Frame(),
            new Frame('bing.php', 'bang', 'boom', 99, new Frame()),
            new Frame(),
            new Frame('whizz.php', line: 24),
        ],
        false,
    ],
]);
