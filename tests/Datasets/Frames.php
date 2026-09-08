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
    'none' => [[]],
    'file' => [['file' => 'foo.php']],
    'line' => [['line' => 42]],
    'function' => [['function' => 'foo']],
    'class' => [['class' => 'foo']],
    'object' => [['object' => (object)[]]],
    'all' => [['file' => 'foo.php', 'line' => 42, 'function' => 'foo', 'class' => 'foo', 'object' => (object)[]]],
    'differently ordered keys' => [[
        'object' => (object)[],
        'file' => 'foo.php',
        'function' => 'foo',
        'line' => 42,
        'class' => 'foo',
    ]],
    'empty values' => [['file' => '', 'line' => 0, 'function' => '', 'class' => '', 'object' => null]],
    'integer keys' => [[
        0 => 'foo.php',
        1 => 42,
        2 => 'foo',
        3 => 'foo',
        4 => (object)[],
    ]],
    'type' => [['type' => '->']],
    'unknown' => [['unknown' => 'foo']],
    'full trace frame' => [[
        'file' => 'foo.php',
        'line' => 42,
        'class' => 'foo',
        'function' => 'foo',
        'object' => (object)[],
        'type' => '->',
        'args' => [],
    ]],
]);

/*
 * @dataset [
 *   Frame: $from,
 *   bool: $empty,
 * ]
 */
dataset('frame objects', [
    'empty' => fn () => new Frame(),
    'file' => fn () => new Frame('foo.php'),
    'class' => fn () => new Frame(class: 'foo'),
    'function' => fn () => new Frame(function: 'foo'),
    'line' => fn () => new Frame(line: 42),
    'object' => fn () => new Frame(object: (object)[]),
    'all' => fn () => new Frame('foo.php', 'foo', 'foo', 42, (object)[]),
    'file function and object' => fn () => new Frame('foo.php', function: 'foo', object: (object)[]),
]);

/*
 * @dataset [
 *   Frame[]: $from,
 *   bool: $empty,
 * ]
 */
dataset('frame arrays', [
    'one frame' => fn () => [
        new Frame('foo.php', 'foo', 'foo', 42, (object) []),
    ],
    'two frames' => fn () => [
        new Frame('foo.php', 'foo', 'foo', 42, (object) []),
        new Frame('bing.php', 'bang', 'boom', 99, new Frame()),
    ],
    'three frames' => fn () => [
        new Frame('foo.php', 'foo', 'foo', 42, (object) []),
        new Frame('bing.php', 'bang', 'boom', 99, new Frame()),
        new Frame('whizz.php', line: 24),
    ],
    'mixed frames' => fn () => [
        new Frame('foo.php', 'foo', 'foo', 42, (object) []),
        new Frame(),
        new Frame('bing.php', 'bang', 'boom', 99, new Frame()),
        new Frame(),
        new Frame('whizz.php', line: 24),
    ],
]);


/*
 * @dataset [
 *   Frame[]: $from,
 *   bool: $empty,
 * ]
 */
dataset('empty frame arrays', [
    'empty' => [[]],
    'empty frames' => fn () => [
        new Frame(),
        new Frame(),
        new Frame(),
    ],
]);
