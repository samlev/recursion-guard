<?php

declare(strict_types=1);

use RecursionGuard\Factory;
use RecursionGuard\Recurser;
use Tests\Support\Stubs\RecurserStub;
use Tests\Support\Stubs\RecursableStub;

covers(Recurser::class);

test('manages instances', function () {
    $instance = Recurser::instance();

    expect($instance)->toBeInstanceOf(Recurser::class)
        ->and(Recurser::instance())->toBe($instance);

    Recurser::flush();

    expect(Recurser::instance())->toBeInstanceOf(Recurser::class)
        ->not->toBe($instance);
});

test('manages recursables', function () {
    $callable = new class () {
        public function __invoke(): void
        {
            //
        }
    };

    $factory = new Factory();

    $instance = new RecurserStub();

    $one = $factory->makeRecursable($callable);
    $two = $factory->makeRecursable($callable);


    expect($instance->find($one))->toBeNull()
        ->and($instance->find($two))->toBeNull();

    $instance->expose_setValue($one);

    expect($instance->find($one))->toBe($one)
        ->and($instance->find($two))->toBe($one)
        ->not->toBe($two);

    $instance->release($one);

    expect($instance->find($one))->toBeNull()
        ->and($instance->find($two))->toBeNull();
});

test('guard executes callable and returns result', function () {
    $executed = false;
    $callable = function () use (&$executed) {
        $executed = true;
        return 'result-value';
    };

    $instance = new RecurserStub();
    $recursable = $instance->expose_factory->makeRecursable($callable);
    $output = $instance->guard($recursable);

    expect($output)->toBe('result-value')
        ->and($executed)->toBeTrue();
});

test('guard returns onRecursion handler when recursable already in stack', function () {
    $factory = new Factory();
    $instance = new RecurserStub();

    $recursable = (new RecursableStub(fn () => 'original-value', 'on-recursion-value'))->state(
        started: true,
        stackDepth: 1,
    );

    $instance->expose_setValue($recursable);

    $output = $instance->guard($recursable);

    expect($output)->toBe('on-recursion-value');
});

test('guard releases recursable in finally block', function () {
    $factory = new Factory();
    $instance = new RecurserStub();

    $callable = fn () => 'result';
    $recursable = $factory->makeRecursable($callable);

    $instance->guard($recursable);

    $defaultScope = $instance->expose_defaultScope;
    $stack = $instance->expose_getStack($defaultScope);

    expect($stack)->not->toHaveKey($recursable->hash());
});

test('guard releases recursable in finally block on exception', function () {
    $exceptionThrown = false;

    $factory = new Factory();
    $instance = new RecurserStub();

    $callable = function () {
        throw new \Exception('test error');
    };

    $recursable = $factory->makeRecursable($callable);

    try {
        $instance->guard($recursable);
    } catch (\Exception $e) {
        $exceptionThrown = true;
    }

    $defaultScope = $instance->expose_defaultScope;
    $stack = $instance->expose_getStack($defaultScope);

    expect($exceptionThrown)->toBeTrue()
        ->and($stack)->not->toHaveKey($recursable->hash());
});

test('guard returns early when recursable is found in stack', function () {
    $factory = new Factory();
    $instance = new RecurserStub();

    $callCount = 0;
    $callable = function () use (&$callCount) {
        $callCount++;
        return 'value-' . $callCount;
    };

    $recursable = (new RecursableStub($callable, 'recursion-response'))->state(
        started: true,
        stackDepth: 1,
    );

    $instance->expose_setValue($recursable);
    $output = $instance->guard($recursable);

    expect($callCount)->toBe(0)
        ->and($output)->toBe('recursion-response');
});
