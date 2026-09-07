<?php

declare(strict_types=1);

use RecursionGuard\Data\Frame;
use RecursionGuard\Exception\InvalidTraceException;

covers(InvalidTraceException::class);

it('makes exception with generated message', function (mixed $from, string $message) {
    $exception = InvalidTraceException::make($from);

    expect($exception)
        ->toBeInstanceOf(InvalidTraceException::class)
        ->toBeInstanceOf(\InvalidArgumentException::class)
        ->and($exception->getMessage())->toBe($message)
        ->and($exception->getCode())->toBe(0)
        ->and($exception->getPrevious())->toBeNull()
        ->and($exception->getInvalidTrace())->toBe($from)
        ->and($exception->getInvalidFrames())->toBe(array_filter($from, fn ($frame) => !($frame instanceof Frame)));
})->with([
    'empty array' => [[], 'Invalid trace frame(s) provided: []'],
    'null frame' => [[null], 'Invalid trace frame(s) provided: [null]'],
    'non-frame array' => [
        [[]],
        'Invalid trace frame(s) provided: [[]]'
    ],
    'multiple non-frame values' => [
        [[], 42, 3.14, Frame::class, ['foo' => 'bar'], (object) ['baz' => 'qux']],
        'Invalid trace frame(s) provided: [[],42,3.14,"RecursionGuard\\\\Data\\\\Frame",{"foo":"bar"},{"baz":"qux"}]',
    ],
    'including valid frames' => [
        [Frame::make(), [], Frame::make(), 'foo', Frame::make(), (object)[]],
        'Invalid trace frame(s) provided: {"1":[],"3":"foo","5":{}}',
    ],
]);

it('makes with exception parts', function (array $parts, string $message, int $code, ?Throwable $previous) {
    $from = ['foo', Frame::make(), []];

    $exception = InvalidTraceException::make($from, ...$parts);

    expect($exception)
        ->toBeInstanceOf(InvalidTraceException::class)
        ->and($exception->getMessage())->toBe($message)
        ->and($exception->getCode())->toBe($code)
        ->and($exception->getPrevious())->toEqual($previous)
        ->and($exception->getInvalidTrace())->toBe($from)
        ->and($exception->getInvalidFrames())->toBe(['foo', 2 => []]);
})->with([
    'none' => [
        [],
        'Invalid trace frame(s) provided: {"0":"foo","2":[]}',
        0,
        null,
    ],
    'message' => [
        ['message' => 'test'],
        'test',
        0,
        null,
    ],
    'code' => [
        ['code' => 42],
        'Invalid trace frame(s) provided: {"0":"foo","2":[]}',
        42,
        null,
    ],
    'previous' => [
        ['previous' => new Exception('foo')],
        'Invalid trace frame(s) provided: {"0":"foo","2":[]}',
        0,
        new Exception('foo'),
    ],
    'all' => [
        ['message' => 'test', 'code' => 42, 'previous' => new Exception('foo')],
        'test',
        42,
        new Exception('foo'),
    ],
]);
