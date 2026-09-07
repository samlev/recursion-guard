<?php

declare(strict_types=1);

use RecursionGuard\Exception\RecursionException;
use RecursionGuard\Recursable;
use Tests\Support\Stubs\RecursableStub;

covers(RecursionException::class);

it('makes with recursable', function () {
    $recursable = new Recursable(fn () => null, signature: 'foo');
    $exception = RecursionException::make($recursable);

    expect($exception)
        ->toBeInstanceOf(RecursionException::class)
        ->toBeInstanceOf(\RuntimeException::class)
        ->and($exception->getRecursable())->toBe($recursable);
});

it('makes exception with generated message', function (Recursable $recursable, string $message) {
    $exception = RecursionException::make($recursable);

    expect($exception)
        ->toBeInstanceOf(RecursionException::class)
        ->toBeInstanceOf(\RuntimeException::class)
        ->and($exception->getMessage())->toBe($message)
        ->and($exception->getCode())->toBe(0)
        ->and($exception->getPrevious())->toBeNull();
})->with([
    'not started' => [
        new Recursable(fn () => null, signature: 'foo'),
        'Call stack for [foo] has not commenced.',
    ],
    'started' => [
        (new RecursableStub(fn () => null, signature: 'foo'))->state(
            started: true,
            stackDepth: 1,
        ),
        'Callback for [foo] has been called recursively.'
    ],
    'finished' => [
        (new RecursableStub(fn () => null, signature: 'foo'))->state(
            started: true,
        ),
        'Call stack for [foo] has completed.'
    ],
    'recursing' => [
        (new RecursableStub(fn () => null, signature: 'foo'))->state(
            started: true,
            stackDepth: 2,
        ),
        'Callback for [foo] has been called while resolving return value.'
    ],
]);

it('makes with exception parts', function (array $parts, string $message, int $code, ?Throwable $previous) {
    $exception = RecursionException::make(new Recursable(fn () => null, signature: 'foo'), ...$parts);

    expect($exception)
        ->toBeInstanceOf(RecursionException::class)
        ->and($exception->getMessage())->toBe($message)
        ->and($exception->getCode())->toBe($code)
        ->and($exception->getPrevious())->toEqual($previous);
})->with([
    'none' => [[], 'Call stack for [foo] has not commenced.', 0, null],
    'message' => [['message' => 'test'], 'test', 0, null],
    'code' => [['code' => 42], 'Call stack for [foo] has not commenced.', 42, null],
    'previous' => [['previous' => new Exception('foo')], 'Call stack for [foo] has not commenced.', 0, new Exception('foo')],
    'all' => [
        ['message' => 'test', 'code' => 42, 'previous' => new Exception('foo')],
        'test',
        42,
        new Exception('foo'),
    ],
]);
