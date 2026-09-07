<?php

declare(strict_types=1);

use RecursionGuard\Data\Frame;
use RecursionGuard\Data\Trace;

/*
 * @dataset [
 *   array[]: $from,
 *   Frame[]: $expected,
 *   Frame[]: $withoutEmtpy,
 * ]
 */
dataset('traces', [
    'none' => fn () => [[], [], []],
    'empty frame' => fn () => [
        [[]],
        [new Frame()],
        [],
    ],
    'two empty frames' => fn () => [
        [[], []],
        [new Frame(), new Frame()],
        [],
    ],
    'one frame' => fn () => [
        [['file' => 'foo.php', 'class' => 'foo', 'function' => 'foo', 'line' => 42, 'object' => (object) []]],
        [new Frame('foo.php', 'foo', 'foo', 42, (object) [])],
        [new Frame('foo.php', 'foo', 'foo', 42, (object) [])],
    ],
    'two frames' => fn () => [
        [
            ['file' => 'foo.php', 'class' => 'foo', 'function' => 'foo', 'line' => 42, 'object' => (object) []],
            ['file' => 'bing.php', 'class' => 'bang', 'function' => 'boom', 'line' => 99, 'object' => new Frame()],
        ],
        [
            new Frame('foo.php', 'foo', 'foo', 42, (object) []),
            new Frame('bing.php', 'bang', 'boom', 99, new Frame()),
        ],
        [
            new Frame('foo.php', 'foo', 'foo', 42, (object) []),
            new Frame('bing.php', 'bang', 'boom', 99, new Frame()),
        ],
    ],
    'three frames' => fn () => [
        [
            ['file' => 'foo.php', 'function' => 'foo', 'class' => 'foo', 'line' => 42, 'object' => (object) []],
            ['file' => 'bing.php', 'class' => 'bang', 'function' => 'boom', 'line' => 99, 'object' => new Frame()],
            ['file' => 'whizz.php', 'line' => 24],
        ],
        [
            new Frame('foo.php', 'foo', 'foo', 42, (object) []),
            new Frame('bing.php', 'bang', 'boom', 99, new Frame()),
            new Frame('whizz.php', line: 24),
        ],
        [
            new Frame('foo.php', 'foo', 'foo', 42, (object) []),
            new Frame('bing.php', 'bang', 'boom', 99, new Frame()),
            new Frame('whizz.php', line: 24),
        ],
    ],
    'mixed frames' => fn () => [
        [
            ['file' => 'foo.php', 'function' => 'foo', 'class' => 'foo', 'line' => 42, 'object' => (object) []],
            [],
            ['file' => 'bing.php', 'class' => 'bang', 'function' => 'boom', 'line' => 99, 'object' => new Frame()],
            [],
            ['file' => 'whizz.php', 'line' => 24],
        ],
        [
            new Frame('foo.php', 'foo', 'foo', 42, (object) []),
            new Frame(),
            new Frame('bing.php', 'bang', 'boom', 99, new Frame()),
            new Frame(),
            new Frame('whizz.php', line: 24),
        ],
        [
            new Frame('foo.php', 'foo', 'foo', 42, (object) []),
            new Frame('bing.php', 'bang', 'boom', 99, new Frame()),
            new Frame('whizz.php', line: 24),
        ],
    ],
]);

/*
 * @dataset [
 *   array[]: $from,
 *   bool: $empty,
 * ]
 */
dataset('trace objects', [
    'one frame' => fn () => new Trace([
        new Frame('foo.php', 'foo', 'foo', 42, (object) []),
    ]),
    'two frames' => fn () => new Trace([
        new Frame('foo.php', 'foo', 'foo', 42, (object) []),
        new Frame('bing.php', 'bang', 'boom', 99, new Frame()),
    ]),
    'three frames' => fn () => new Trace([
        new Frame('foo.php', 'foo', 'foo', 42, (object) []),
        new Frame('bing.php', 'bang', 'boom', 99, new Frame()),
        new Frame('whizz.php', line: 24),
    ]),
    'mixed frames' => fn () => new Trace([
        new Frame('foo.php', 'foo', 'foo', 42, (object) []),
        new Frame(),
        new Frame('bing.php', 'bang', 'boom', 99, new Frame()),
        new Frame(),
        new Frame('whizz.php', line: 24),
    ]),
    'second semi-empty frame' => fn () => new Trace([
        new Frame('foo.php', 'foo', 'foo', 42, (object) []),
        new Frame('bar.php', '', '', 0, null),
    ]),
]);


/*
 * @dataset [
 *   array[]: $from,
 *   bool: $empty,
 * ]
 */
dataset('empty trace objects', [
    'no frames' => fn () => new Trace(),
    'empty frames' => fn () => new Trace([
        new Frame(),
        new Frame(),
        new Frame(),
    ]),
]);

/*
 * @dataset [
 *   array[]: $from,
 * ]
 */
dataset('invalid trace arrays', [
    'boolean' => [[true]],
    'string' => [['foo']],
    'integer' => [[42]],
    'float' => [[3.14]],
    'array' => [[[]]],
    'non-frame object' => fn () => [[new stdClass()]],
    'valid frame array' => [[['file' => 'foo.php', 'function' => 'foo', 'class' => 'foo', 'line' => 42]]],
    'mixed frames' => fn () => [[
        new Frame(),
        ['file' => 'foo.php', 'function' => 'foo', 'class' => 'foo', 'line' => 42],
        new Frame()
    ]],
]);
