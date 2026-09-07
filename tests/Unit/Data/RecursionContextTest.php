<?php

declare(strict_types=1);

use RecursionGuard\Data\RecursionContext;

covers(RecursionContext::class);

it('creates new with defaults', function ($from, $signature) {
    $context = new RecursionContext(...$from);

    expect($context->file)->toBe($from['file'] ?? '')
        ->and($context->function)->toBe($from['function'] ?? '')
        ->and($context->class)->toBe($from['class'] ?? '')
        ->and($context->line)->toBe($from['line'] ?? 0)
        ->and($context->object)->toBe($from['object'] ?? null)
        ->and($context->signature)->toBe($signature);
})->with('contexts');

it('only allows array read access to properties', function ($offset, $set, $exists, $value) {
    $context = new RecursionContext(
        'foo.php',
        'baz',
        'bar',
        42,
        (object) [],
    );

    $message = RecursionContext::class . ' is read-only';

    expect(fn () => $context->offsetSet($offset, $set))->toThrow(\RuntimeException::class, $message)
        ->and($context->offsetGet($offset))->toEqual($value)
        ->and(fn () => $context->offsetUnset($offset))->toThrow(\RuntimeException::class, $message)
        ->and($context->offsetExists($offset))->toEqual($exists)
        ->and(function () use (&$context, $offset, $set) {
            $context[$offset] = $set;
        })->toThrow(\RuntimeException::class, $message)
        ->and($context[$offset])->toEqual($value)
        ->and(function () use (&$context, $offset) {
            unset($context[$offset]);
        })->toThrow(\RuntimeException::class, $message)
        ->and(isset($context[$offset]))->toEqual($exists);
})->with([
    'file' => ['file', 'bing.php', true, 'foo.php'],
    'function' => ['function', 'bang', true, 'bar'],
    'class' => ['class', 'bong', true, 'baz'],
    'line' => ['line', 99, true, 42],
    'object' => ['object', new RecursionContext(), true, (object) []],
    'signature' => ['signature', 'bing.php:bang@bong', true, 'foo.php:baz@bar'],
    'unknown string' => ['foo', 'foo', false, null],
    'static method' => ['make', 'bar', false, null],
    'instance method' => ['jsonSerialize', 'bing', false, null],
    'first index' => [0, 1, false, null],
    'last index' => [5, 6, false, null],
    'random index' => [random_int(PHP_INT_MIN, PHP_INT_MAX), 42, false, null],
]);
