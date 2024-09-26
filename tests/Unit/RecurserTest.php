<?php

declare(strict_types=1);

use RecursionGuard\Recursable;
use RecursionGuard\Recurser;

covers(Recurser::class);

test('instance creates a new instancebif one does not exist', function () {
    $instance = Recurser::instance();

    expect($instance)->toBeInstanceOf(Recurser::class)
        ->and(Recurser::instance())->toBe($instance);

    Recurser::flush();

    expect(Recurser::instance())->toBeInstanceOf(Recurser::class)
        ->not->toBe($instance);
});

test('', function () {
    $callable = new class () {
        public function __invoke(): void
        {
            //
        }
    };

    $one = Recursable::make($callable);
    $two = Recursable::make($callable);

    $instance = new class () extends Recurser {
        public function set(Recursable $target): void
        {
            $this->setValue($target);
        }
    };

    expect($instance->find($one))->toBeNull()
        ->and($instance->find($two))->toBeNull();

    $instance->set($one);

    expect($instance->find($one))->toBe($one)
        ->and($instance->find($two))->toBe($one)
        ->not->toBe($two);

    $instance->release($one);

    expect($instance->find($one))->toBeNull()
        ->and($instance->find($two))->toBeNull();
});
