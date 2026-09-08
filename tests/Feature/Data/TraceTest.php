<?php

declare(strict_types=1);

use RecursionGuard\Data\Frame;
use RecursionGuard\Data\Trace;
use RecursionGuard\Exception\InvalidTraceException;

covers(
    Trace::class,
    Frame::class,
    \RecursionGuard\Exception\InvalidTraceException::class,
    \RecursionGuard\Factory::class,
    \RecursionGuard\Recurser::class,
);

it('creates new from an array of frames', function (array $from) {
    $trace = new Trace($from);

    expect($trace->count())->toEqual(count($from))
        ->and(count($trace))->toEqual(count($from))
        ->and($trace->frames)->toEqual($from)
        ->and($trace->empty())->toBeFalse()
        ->and($trace->jsonSerialize())->toEqual($from);
})->with('frame arrays');

it('creates new from an array of empty frames', function (array $from) {
    $trace = new Trace($from);

    expect($trace->count())->toEqual(count($from))
        ->and(count($trace))->toEqual(count($from))
        ->and($trace->frames)->toEqual($from)
        ->and($trace->empty())->toBeTrue()
        ->and($trace->jsonSerialize())->toEqual($from);
})->with('empty frame arrays');

it('throws exception on new with invalid frames', function (array $from) {
    new Trace($from);
})->throws(InvalidTraceException::class)
    ->with('invalid trace arrays');

it('is countable', function (array $from) {
    $trace = new Trace($from);

    expect($trace->count())->toEqual(count($from))
        ->and(count($trace))->toEqual(count($from))
        ->and($trace->count())->toEqual(count($trace->frames))
        ->and(count($trace))->toEqual(count($trace->frames));
})->with('frame arrays');

it('is not empty if a frame is not empty', function (array $from) {
    $trace = new Trace($from);

    expect($trace->empty())->toBeFalse();
})->with('frame arrays');

it('is empty if all frames are empty', function (array $from) {
    $trace = new Trace($from);

    expect($trace->empty())->toBeTrue();
})->with('empty frame arrays');

it('makes from trace array', function (array $from, array $expected, array $withoutEmpty) {
    $trace = Trace::make($from);

    expect($trace->count())->toEqual(count($expected))
        ->and($trace->frames)->toEqual($expected)
        ->and($trace->frames())->toEqual($withoutEmpty)
        ->and($trace->frames(true))->toEqual($expected)
        ->and($trace->empty())->toEqual(empty($withoutEmpty))
        ->and($trace->jsonSerialize())->toEqual($expected);
})->with('traces');

it('makes from array of frames', function (array $from) {
    $trace = Trace::make($from);

    expect($trace->count())->toEqual(count($from))
        ->and($trace->frames)->toEqual($from)
        ->and($trace->empty())->toBeFalse()
        ->and($trace->jsonSerialize())->toEqual($from);
})->with('frame arrays');

it('makes from array of empty frames', function (array $from) {
    $trace = Trace::make($from);

    expect($trace->count())->toEqual(count($from))
        ->and($trace->frames)->toEqual($from)
        ->and($trace->empty())->toBeTrue()
        ->and($trace->jsonSerialize())->toEqual($from);
})->with('empty frame arrays');

it('makes from an existing trace', function (Trace $from) {
    $trace = Trace::make($from);

    expect($trace->count())->toEqual($from->count())
        ->and($trace->frames)->toEqual($from->frames)
        ->and($trace->frames())->toEqual($from->frames())
        ->and($trace->frames(true))->toEqual($from->frames(true))
        ->and($trace->empty())->toEqual(false)
        ->and($trace->jsonSerialize())->toEqual($from->jsonSerialize());
})->with('trace objects');

it('makes from an empty trace', function (Trace $from) {
    $trace = Trace::make($from);

    expect($trace->count())->toEqual($from->count())
        ->and($trace->frames)->toEqual($from->frames)
        ->and($trace->frames())->toEqual($from->frames())
        ->and($trace->frames(true))->toEqual($from->frames(true))
        ->and($trace->empty())->toEqual(true)
        ->and($trace->jsonSerialize())->toEqual($from->jsonSerialize());
})->with('empty trace objects');

it('excludes empty frames with frames method', function (array $from, array $expected, array $withoutEmpty) {
    $trace = Trace::make($from);

    expect($trace->frames())->toEqual($withoutEmpty)
        ->and(count($trace->frames()))->toEqual(count($withoutEmpty))
        ->and($trace->frames(true))->toEqual($expected);
})->with('traces');

it('only allows array read access to properties', function ($offset, $exists, $value) {
    $trace = Trace::make([
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
    'first index' => fn () => [0, true, new Frame('foo.php', 'foo', 'foo', 42, (object) [])],
    'second index' => fn () => [1, true, new Frame()],
    'last index' => fn () => [2, true, new Frame('bing.php', 'bang', 'boom', 99, new Frame())],
    'text index' => ['foo', false, null],
    'random positive index' => fn () => [random_int(3, PHP_INT_MAX), false, null],
    'random negative index' => fn () => [random_int(PHP_INT_MIN, -1), false, null],
]);

it('reindexes frames to sequential numeric keys', function () {
    $frames = [
        'foo' => new Frame('foo.php', 'foo', 'foo', 42, (object) []),
        'empty' => new Frame(),
        'bar' => new Frame('bar.php', 'bar', 'bar', 10, new Frame()),
        'empty-2' => new Frame(),
        'baz' => new Frame('baz.php', 'baz', 'baz', 20, new Frame()),
    ];

    $trace = new Trace($frames);

    expect($trace->frames[0])->toEqual($frames['foo'])
        ->and($trace->frames[1])->toEqual($frames['empty'])
        ->and($trace->frames[2])->toEqual($frames['bar'])
        ->and($trace->frames[3])->toEqual($frames['empty-2'])
        ->and($trace->frames[4])->toEqual($frames['baz'])
        ->and(array_keys($trace->frames))->toEqual([0, 1, 2, 3, 4])
        ->and($trace->frames())->toHaveCount(3)
        ->and(array_keys($trace->frames()))->toEqual([0, 1, 2])
        ->and($trace->frames()[0])->toEqual($frames['foo'])
        ->and($trace->frames()[1])->toEqual($frames['bar'])
        ->and($trace->frames()[2])->toEqual($frames['baz'])
        ->and($trace->frames(true))->toHaveCount(5)
        ->and($trace->frames(true))->toBe($trace->frames);
});
