<?php

declare(strict_types=1);

use RecursionGuard\Data\Frame;

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
