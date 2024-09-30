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
    'none' => [[], [], []],
    'empty frame' => [
        [[]],
        [new Frame()],
        [],
    ],
    'two empty frames' => [
        [[], []],
        [new Frame(), new Frame()],
        [],
    ],
    'one frame' => [
        [['file' => 'foo.php', 'class' => 'foo', 'function' => 'foo', 'line' => 42, 'object' => (object) []]],
        [new Frame('foo.php', 'foo', 'foo', 42, (object) [])],
        [new Frame('foo.php', 'foo', 'foo', 42, (object) [])],
    ],
    'two frames' => [
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
    'three frames' => [
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
    'mixed frames' => [
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
    'empty' => [new Trace(), true],
    'one frame' => [
        new Trace([
            new Frame('foo.php', 'foo', 'foo', 42, (object) []),
        ]),
        false,
    ],
    'two frames' => [
        new Trace([
            new Frame('foo.php', 'foo', 'foo', 42, (object) []),
            new Frame('bing.php', 'bang', 'boom', 99, new Frame()),
        ]),
        false,
    ],
    'three frames' => [
        new Trace([
            new Frame('foo.php', 'foo', 'foo', 42, (object) []),
            new Frame('bing.php', 'bang', 'boom', 99, new Frame()),
            new Frame('whizz.php', line: 24),
        ]),
        false,
    ],
    'empty frames' => [
        new Trace([
            new Frame(),
            new Frame(),
            new Frame(),
        ]),
        true,
    ],
    'mixed frames' => [
        new Trace([
            new Frame('foo.php', 'foo', 'foo', 42, (object) []),
            new Frame(),
            new Frame('bing.php', 'bang', 'boom', 99, new Frame()),
            new Frame(),
            new Frame('whizz.php', line: 24),
        ]),
        false,
    ],
]);

/*
 * @dataset [
 *   Frame[]: $from,
 *   Frame[]: $expected,
 *   Frame[]: $withoutEmtpy,
 * ]
 */
dataset('valid frame arrays', [
    'no frames' => [[], [], []],
    'one frame' => [
        [new Frame('foo.php', 'foo', 'foo', 42, (object)[])],
        [new Frame('foo.php', 'foo', 'foo', 42, (object)[])],
        [new Frame('foo.php', 'foo', 'foo', 42, (object)[])],
    ],
    'two frames' => [
        [
            new Frame('foo.php', 'foo', 'foo', 42, (object)[]),
            new Frame('bing.php', 'bang', 'boom', 99, new Frame()),
        ],
        [
            new Frame('foo.php', 'foo', 'foo', 42, (object)[]),
            new Frame('bing.php', 'bang', 'boom', 99, new Frame()),
        ],
        [
            new Frame('foo.php', 'foo', 'foo', 42, (object)[]),
            new Frame('bing.php', 'bang', 'boom', 99, new Frame()),
        ],
    ],
    'three frames' => [
        [
            new Frame('foo.php', 'foo', 'foo', 42, (object)[]),
            new Frame('bing.php', 'bang', 'boom', 99, new Frame()),
            new Frame('whizz.php', line: 24),
        ],
        [
            new Frame('foo.php', 'foo', 'foo', 42, (object)[]),
            new Frame('bing.php', 'bang', 'boom', 99, new Frame()),
            new Frame('whizz.php', line: 24),
        ],
        [
            new Frame('foo.php', 'foo', 'foo', 42, (object)[]),
            new Frame('bing.php', 'bang', 'boom', 99, new Frame()),
            new Frame('whizz.php', line: 24),
        ],
    ],
    'empty frames' => [
        [
            new Frame(),
            new Frame(),
            new Frame(),
        ],
        [
            new Frame(),
            new Frame(),
            new Frame(),
        ],
        [],
    ]
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
    'non-frame object' => [[new stdClass()]],
    'valid frame array' => [[['file' => 'foo.php', 'function' => 'foo', 'class' => 'foo', 'line' => 42]]],
    'mixed frames' => [[
        new Frame(),
        ['file' => 'foo.php', 'function' => 'foo', 'class' => 'foo', 'line' => 42],
        new Frame()
    ]],
]);
