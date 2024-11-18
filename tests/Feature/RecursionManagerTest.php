<?php

declare(strict_types=1);

use RecursionGuard\Recurser;

it('prevents recursion in closure', function () {
    $calls = 0;
    $callback = 0;

    $method = function () use (&$method, &$calls, &$callback) {
        $calls++;
        return Recurser::call(function () use ($method, &$callback) {
            $callback++;

            return [$method(), $method(), $method()];
        }, $calls);
    };

    expect($method())->toEqual([1, 1, 1])
        ->and($calls)->toBe(4)
        ->and($callback)->toBe(1)
        ->and($method())->toEqual([5, 5, 5])
        ->and($calls)->toBe(8)
        ->and($callback)->toBe(2);
});
