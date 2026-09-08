<?php

declare(strict_types=1);

use RecursionGuard\Exception\RecursionException;
use RecursionGuard\Recursable;
use Tests\Support\SetsState;

covers(RecursionException::class, Recursable::class);

it('makes with recursable', function () {
    $recursable = new Recursable(fn () => null, signature: 'foo');

    $exception = RecursionException::make($recursable);

    expect($exception)
        ->toBeInstanceOf(RecursionException::class)
        ->and($exception->getRecursable())->toBe($recursable);
});

it('makes default exception message when not started', function () {
    expect(RecursionException::makeMessage(new Recursable(fn () => null, signature: 'foo')))
        ->toBe(sprintf(RecursionException::MESSAGE_DEFAULT, 'foo'));
});

it('makes started exception message when started but not recursing', function () {
    expect(
        RecursionException::makeMessage(
            (new class (fn () => null, signature: 'foo') extends Recursable {
                use SetsState;
            })->state(started: true, stackDepth: 1)
        )
    )->toBe(sprintf(RecursionException::MESSAGE_STARTED, 'foo'));
});

it('makes recursing exception message when recursing but not finished', function () {
    expect(
        RecursionException::makeMessage(
            (new class (fn () => null, signature: 'foo') extends Recursable {
                use SetsState;
            })->state(started: true, stackDepth: 2)
        )
    )->toBe(sprintf(RecursionException::MESSAGE_RECURSING, 'foo'));
});

it('makes finished exception message when finished', function () {
    expect(RecursionException::makeMessage(
        (new class (fn () => null, signature: 'foo') extends Recursable {
            use SetsState;
        })->state(started: true)
    ))->toBe(sprintf(RecursionException::MESSAGE_FINISHED, 'foo'));
});

it('makes overflown exception message when overflown', function () {
    expect(RecursionException::makeMessage(
        (new class (fn () => null, signature: 'foo') extends Recursable {
            use SetsState;
        })->state(stackDepth: -1)
    ))->toBe(sprintf(RecursionException::MESSAGE_OVERFLOW, 'foo'));
});

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
    'previous' => fn () => [
        ['previous' => new Exception('foo')],
        'Call stack for [foo] has not commenced.',
        0,
        new Exception('foo')
    ],
    'all' => fn () => [
        ['message' => 'test', 'code' => 42, 'previous' => new Exception('foo')],
        'test',
        42,
        new Exception('foo'),
    ],
]);

it('sets the recursable once', function () {
    $one = new Recursable(fn () => null, signature: 'foo');
    $two = new Recursable(fn () => null, signature: 'bar');

    $exception = new RecursionException();

    expect($exception->getRecursable())->toBeNull()
        ->and($exception->withRecursable($one)->getRecursable())->toBe($one)
        ->and($exception->withRecursable($two)->getRecursable())->toBe($one);
});
