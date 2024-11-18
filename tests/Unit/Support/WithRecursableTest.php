<?php

declare(strict_types=1);

use Mockery as m;
use RecursionGuard\Recursable;
use RecursionGuard\Support\WithRecursable;
use Tests\Support\Stubs\WithRecursableStub;

mutates(WithRecursable::class);

it('makes the base class', function () {
    $recursable = new Recursable(fn () => null, signature: 'foo');

    $class = m::mock(WithRecursableStub::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $class->shouldReceive('makeMessage')
        ->withArgs(fn (Recursable $r) => $r === $recursable)
        ->once()
        ->andReturn('test');

    $result = $class::make($recursable);

    expect($result)->toBeInstanceOf(WithRecursableStub::class)
        ->not->toBeInstanceOf($class::class)
        ->and($result->getRecursable())->toBe($recursable)
        ->and($result->message)->toBe('test')
        ->and($result->code)->toBe(0)
        ->and($result->previous)->toBeNull();
});

it('should make with a recursable', function () {
    $recursable = new Recursable(fn () => null, signature: 'foo');

    $class = WithRecursableStub::make($recursable);

    expect($class->getRecursable())
        ->toBe($recursable)
        ->and($class->message)->toBe('from: foo')
        ->and($class->code)->toBe(0)
        ->and($class->previous)->toBeNull();
});

it('does not overwrite the recursable', function () {
    $recursable = new Recursable(fn () => null, signature: 'foo');
    $other = new Recursable(fn () => null, signature: 'bar');

    $one = WithRecursableStub::make($recursable);
    $one->expose_withRecursable($other);

    expect($one->getRecursable())
        ->toBe($recursable);

    $two = new WithRecursableStub();

    expect($two->getRecursable())
        ->toBeNull();

    $two->expose_withRecursable($other);

    expect($two->getRecursable())
        ->toBe($other);

    $two->expose_withRecursable($recursable);

    expect($two->getRecursable())
        ->toBe($other);
});

it('makes with parts', function (array $parts, string $message, int $code, ?Throwable $previous) {
    $recursable = new Recursable(fn () => null, signature: 'foo');

    $class = WithRecursableStub::make($recursable, ...$parts);

    expect($class->getRecursable())
        ->toBe($recursable)
        ->and($class->message)->toBe($message)
        ->and($class->code)->toBe($code)
        ->and($class->previous)->toEqual($previous);
})->with([
    'none' => [[], 'from: foo', 0, null],
    'message' => [['message' => 'test'], 'test', 0, null],
    'code' => [['code' => 42], 'from: foo', 42, null],
    'previous' => [['previous' => new Exception('foo')], 'from: foo', 0, new Exception('foo')],
    'all' => [
        ['message' => 'test', 'code' => 42, 'previous' => new Exception('foo')],
        'test',
        42,
        new Exception('foo'),
    ],
]);
