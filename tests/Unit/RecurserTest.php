<?php

declare(strict_types=1);

use RecursionGuard\Factory;
use RecursionGuard\Recurser;
use Tests\Support\Stubs\RecurserStub;

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
