<?php

declare(strict_types=1);

use RecursionGuard\Data\Frame;
use RecursionGuard\Data\Trace;

covers(Trace::class);

it('makes new from array', function ($from, $expected, $withoutEmpty) {
    $trace = new Trace($from);

    expect($trace->count())->toEqual(count($expected))
        ->and($trace->frames)->toEqual($expected)
        ->and($trace->frames())->toEqual($withoutEmpty)
        ->and($trace->frames(true))->toEqual($expected)
        ->and($trace->empty())->toEqual(empty($withoutEmpty))
        ->and($trace->jsonSerialize())->toEqual($expected);
})->with('traces');

it('only allows array read access to properties', function ($offset, $exists, $value) {
    $trace = new Trace([
        ['file' => 'foo.php', 'class' => 'foo', 'function' => 'foo', 'line' => 42, 'object' => (object) []],
        [], // empty frame
        ['file' => 'bing.php', 'class' => 'bang', 'function' => 'boom', 'line' => 99, 'object' => new Frame()],
    ]);

    $set = new Frame('whizz.php', line: 24);

    $message = Trace::class . ' is read-only';

    expect(fn () => $trace->offsetSet($offset, $set))->toThrow(\RuntimeException::class, $message)
        ->and($trace->offsetGet($offset))->toEqual($value)
        ->and(fn () => $trace->offsetUnset($offset))->toThrow(\RuntimeException::class, $message)
        ->and($trace->offsetExists($offset))->toEqual($exists)
        ->and(function () use (&$trace, $offset, $set) {
            $trace[$offset] = $set;
        })->toThrow(\RuntimeException::class, $message)
        ->and($trace[$offset])->toEqual($value)
        ->and(function () use (&$trace, $offset) {
            unset($trace[$offset]);
        })->toThrow(\RuntimeException::class, $message)
        ->and(isset($trace[$offset]))->toEqual($exists);
})->with([
    'first index' => [0, true, new Frame('foo.php', 'foo', 'foo', 42, (object) [])],
    'second index' => [1, true, new Frame()],
    'last index' => [2, true, new Frame('bing.php', 'bang', 'boom', 99, new Frame())],
    'text index' => ['foo', false, null],
    'random positive index' => [random_int(3, PHP_INT_MAX), false, null],
    'random negative index' => [random_int(PHP_INT_MIN, -1), false, null],
]);
