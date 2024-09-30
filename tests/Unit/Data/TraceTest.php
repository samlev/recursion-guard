<?php

declare(strict_types=1);

use RecursionGuard\Data\Frame;
use RecursionGuard\Data\Trace;
use RecursionGuard\Exception\InvalidTraceException;

covers(Trace::class);

it('creates new from an array of frames', function ($from, $empty) {
    $trace = new Trace($from);

    expect($trace->count())->toEqual(count($from))
        ->and(count($trace))->toEqual(count($from))
        ->and($trace->frames)->toEqual($from)
        ->and($trace->empty())->toEqual($empty)
        ->and($trace->jsonSerialize())->toEqual($from);
})->with('frame arrays');

it('throws exception on new with invalid frames', function ($from) {
    new Trace($from);
})->throws(InvalidTraceException::class)
    ->with('invalid trace arrays');

it('is countable', function ($from) {
    $trace = new Trace($from);

    expect($trace->count())->toEqual(count($from))
        ->and(count($trace))->toEqual(count($from))
        ->and($trace->count())->toEqual(count($trace->frames))
        ->and(count($trace))->toEqual(count($trace->frames));
})->with('frame arrays');

it('is empty if all frames are empty', function ($from, $empty) {
    $trace = new Trace($from);

    expect($trace->empty())->toBe($empty);
})->with('frame arrays');

it('makes from trace array', function ($from, $expected, $withoutEmpty) {
    $trace = Trace::make($from);

    expect($trace->count())->toEqual(count($expected))
        ->and($trace->frames)->toEqual($expected)
        ->and($trace->frames())->toEqual($withoutEmpty)
        ->and($trace->frames(true))->toEqual($expected)
        ->and($trace->empty())->toEqual(empty($withoutEmpty))
        ->and($trace->jsonSerialize())->toEqual($expected);
})->with('traces');

it('makes from array of frames', function ($from, $empty) {
    $trace = Trace::make($from);

    expect($trace->count())->toEqual(count($from))
        ->and($trace->frames)->toEqual($from)
        ->and($trace->empty())->toEqual($empty)
        ->and($trace->jsonSerialize())->toEqual($from);
})->with('frame arrays');

it('makes from an existing trace', function ($from, $empty) {
    $trace = Trace::make($from);

    expect($trace->count())->toEqual($from->count())
        ->and($trace->frames)->toEqual($from->frames)
        ->and($trace->frames())->toEqual($from->frames())
        ->and($trace->frames(true))->toEqual($from->frames(true))
        ->and($trace->empty())->toEqual($empty)
        ->and($trace->jsonSerialize())->toEqual($from->jsonSerialize());
})->with('trace objects');

it('excludes empty frames with frames method', function ($from, $expected, $withoutEmpty) {
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
    'first index' => [0, true, new Frame('foo.php', 'foo', 'foo', 42, (object) [])],
    'second index' => [1, true, new Frame()],
    'last index' => [2, true, new Frame('bing.php', 'bang', 'boom', 99, new Frame())],
    'text index' => ['foo', false, null],
    'random positive index' => [random_int(3, PHP_INT_MAX), false, null],
    'random negative index' => [random_int(PHP_INT_MIN, -1), false, null],
]);
