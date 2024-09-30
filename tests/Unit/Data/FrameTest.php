<?php

declare(strict_types=1);

use RecursionGuard\Data\Frame;
use RecursionGuard\Data\RecursionContext;

covers(Frame::class);

it('makes a new frame that is empty', function () {
    $frame = new Frame();

    expect($frame->empty())->toBeTrue()
        ->and($frame->file)->toBe('')
        ->and($frame['file'])->toBe('')
        ->and($frame->class)->toBe('')
        ->and($frame['class'])->toBe('')
        ->and($frame->function)->toBe('')
        ->and($frame['function'])->toBe('')
        ->and($frame->line)->toBe(0)
        ->and($frame['line'])->toBe(0)
        ->and($frame->object)->toBeNull()
        ->and($frame['object'])->toBeNull();
});

it('creates new with defaults', function ($from, $empty) {
    // Grab only the keys we want to test
    $from = array_intersect_key($from, array_flip(['file', 'class', 'function', 'line', 'object']));

    $frame = new Frame(...$from);

    $file = $from['file'] ?? '';
    $class = $from['class'] ?? '';
    $function = $from['function'] ?? '';
    $line = $from['line'] ?? 0;
    $object = $from['object'] ?? null;

    expect($frame->file)->toBe($file)
        ->and($frame['file'])->toBe($file)
        ->and($frame->class)->toBe($class)
        ->and($frame['class'])->toBe($class)
        ->and($frame->function)->toBe($function)
        ->and($frame['function'])->toBe($function)
        ->and($frame->line)->toBe($line)
        ->and($frame['line'])->toBe($line)
        ->and($frame->object)->toBe($object)
        ->and($frame['object'])->toBe($object)
        ->and($frame->jsonSerialize())->toBe([
            'file' => $file,
            'class' => $class,
            'function' => $function,
            'line' => $line,
            'object' => $object,
        ])
        ->and($frame->empty())->toBe($empty);
})->with('frames');

it('makes with defaults', function ($from, $empty) {
    $frame = Frame::make($from);

    $file = $from['file'] ?? '';
    $class = $from['class'] ?? '';
    $function = $from['function'] ?? '';
    $line = $from['line'] ?? 0;
    $object = $from['object'] ?? null;

    expect($frame->file)->toBe($file)
        ->and($frame['file'])->toBe($file)
        ->and($frame->class)->toBe($class)
        ->and($frame['class'])->toBe($class)
        ->and($frame->function)->toBe($function)
        ->and($frame['function'])->toBe($function)
        ->and($frame->line)->toBe($line)
        ->and($frame['line'])->toBe($line)
        ->and($frame->object)->toBe($object)
        ->and($frame['object'])->toBe($object)
        ->and($frame->jsonSerialize())->toBe([
            'file' => $file,
            'class' => $class,
            'function' => $function,
            'line' => $line,
            'object' => $object,
        ])
        ->and($frame->empty())->toBe($empty);
})->with('frames');

it('clones frame when making from existing frame', function ($from, $empty) {
    $frame = Frame::make($from);

    expect($frame)->not->toBe($from)
        ->and($frame)->toEqual($from)
        ->and($frame->file)->toBe($from->file)
        ->and($frame['file'])->toBe($from['file'])
        ->and($frame->function)->toBe($from->function)
        ->and($frame['function'])->toBe($from['function'])
        ->and($frame->class)->toBe($from->class)
        ->and($frame['class'])->toBe($from['class'])
        ->and($frame->line)->toBe($from->line)
        ->and($frame['line'])->toBe($from['line'])
        ->and($frame->object)->toBe($from->object)
        ->and($frame['object'])->toBe($from['object'])
        ->and($frame->jsonSerialize())->toBe($from->jsonSerialize())
        ->and($frame->empty())->toBe($empty)
        ->and($frame->empty())->toBe($from->empty());
})->with('frame objects');

it('only allows array read access to properties', function ($offset, $set, $exists, $value) {
    $frame = new Frame(
        'foo.php',
        'baz',
        'bar',
        42,
        (object)[],
    );

    $message = Frame::class . ' is read-only';

    expect(fn () => $frame->offsetSet($offset, $set))->toThrow(\RuntimeException::class, $message)
        ->and($frame->offsetGet($offset))->toEqual($value)
        ->and(fn () => $frame->offsetUnset($offset))->toThrow(\RuntimeException::class, $message)
        ->and($frame->offsetExists($offset))->toEqual($exists)
        ->and(function () use (&$frame, $offset, $set) {
            $frame[$offset] = $set;
        })->toThrow(\RuntimeException::class, $message)
        ->and($frame[$offset])->toEqual($value)
        ->and(function () use (&$frame, $offset) {
            unset($frame[$offset]);
        })->toThrow(\RuntimeException::class, $message)
        ->and(isset($frame[$offset]))->toEqual($exists);
})->with([
    'file' => ['file', 'bing.php', true, 'foo.php'],
    'line' => ['line', 99, true, 42],
    'class' => ['class', 'bang', true, 'baz'],
    'function' => ['function', 'bong', true, 'bar'],
    'object' => ['object', new RecursionContext(), true, (object)[]],
    'signature' => ['signature', 'bing.php:bang@bong', false, null],
    'unknown string' => ['foo', 'foo', false, null],
    'static method' => ['make', 'bar', false, null],
    'instance method' => ['jsonSerialize', 'bing', false, null],
    'first index' => [0, 1, false, null],
    'last index' => [5, 6, false, null],
    'random index' => [random_int(PHP_INT_MIN, PHP_INT_MAX), 42, false, null],
]);
