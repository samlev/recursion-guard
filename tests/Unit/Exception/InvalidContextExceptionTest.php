<?php

declare(strict_types=1);

use RecursionGuard\Data\Trace;
use RecursionGuard\Exception\InvalidContextException;

covers(InvalidContextException::class);

test('it makes exception with generated message', function (mixed $from, string $message) {
    $exception = InvalidContextException::make($from);

    expect($exception)
        ->toBeInstanceOf(InvalidContextException::class)
        ->toBeInstanceOf(\InvalidArgumentException::class)
        ->and($exception->getMessage())->toBe($message)
        ->and($exception->getCode())->toBe(0)
        ->and($exception->getPrevious())->toBeNull();
})->with([
    'invalid array' => [['foo' => 'bar'], 'Invalid backtrace provided: {"foo":"bar"}'],
    'empty array' => [[], 'Empty backtrace provided.'],
    'empty trace' => [Trace::make([]), 'Empty backtrace provided.'],
    'callable' => [
        function () {
            return 'foo';
        },
        'Invalid context provided.'
    ],
    'null' => [null, 'Invalid context provided.'],
]);

test('it makes with exception parts', function (array $parts, string $message, int $code, ?Throwable $previous) {
    $exception = InvalidContextException::make(null, ...$parts);

    expect($exception)
        ->toBeInstanceOf(InvalidContextException::class)
        ->and($exception->getMessage())->toBe($message)
        ->and($exception->getCode())->toBe($code)
        ->and($exception->getPrevious())->toEqual($previous);
})->with([
    'none' => [[], 'Invalid context provided.', 0, null],
    'message' => [['message' => 'test'], 'test', 0, null],
    'code' => [['code' => 42], 'Invalid context provided.', 42, null],
    'previous' => [['previous' => new Exception('foo')], 'Invalid context provided.', 0, new Exception('foo')],
    'all' => [
        ['message' => 'test', 'code' => 42, 'previous' => new Exception('foo')],
        'test',
        42,
        new Exception('foo'),
    ],
]);
