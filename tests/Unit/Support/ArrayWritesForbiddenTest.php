<?php

declare(strict_types=1);

use RecursionGuard\Support\ArrayWritesForbidden;

covers(ArrayWritesForbidden::class);

it('throws exception with on offset functions', function ($offset) {
    $mock = new class () implements \ArrayAccess {
        use ArrayWritesForbidden;

        public function offsetExists(mixed $offset): bool
        {
            return true;
        }

        public function offsetGet(mixed $offset): mixed
        {
            return null;
        }
    };

    $message = $mock::class . ' is read-only';

    expect(fn () => $mock->offsetSet($offset, 'bar'))
        ->toThrow(\RuntimeException::class, $message)
        ->and(function () use (&$mock, $offset) {
            $mock[$offset] = 'bar';
        })
        ->toThrow(\RuntimeException::class, $message)
        ->and(fn () => $mock->offsetUnset($offset))
        ->toThrow(\RuntimeException::class, $message)
        ->and(function () use (&$mock, $offset) {
            unset($mock[$offset]);
        })
        ->toThrow(\RuntimeException::class, $message);
})->with([
    'string' => ['foo'],
    'first index' => [0],
    'random integer' => [random_int(PHP_INT_MIN, PHP_INT_MAX)],
]);
