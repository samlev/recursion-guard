<?php

declare(strict_types=1);

use RecursionGuard\Recurser;
use Tests\Support\Feature\Model;

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

it('uses cached on recursion value', function () {
    $calls = 0;
    $callback = 0;
    $recursion = 0;

    $method = function ($times = 0) use (&$method, &$calls, &$callback, &$recursion) {
        $calls++;
        return Recurser::call(
            function () use ($times, $method, &$callback) {
                $callback++;

                $value = [];

                for ($i = 0; $i < $times; $i++) {
                    $value[] = $method();
                }

                return $value;
            },
            // This will increment on each recursion, but the value at the time of the original call cached for return.
            $recursion++,
        );
    };

    expect($method(3))->toEqual([0, 0, 0])
        ->and($calls)->toBe(4)
        ->and($callback)->toBe(1)
        ->and($recursion)->toBe(4)
        ->and($method(2))->toEqual([4, 4])
        ->and($calls)->toBe(7)
        ->and($callback)->toBe(2)
        ->and($recursion)->toBe(7);
});

it('calls on recursion callback only if recursion happens and caches result', function () {
    $calls = 0;
    $callback = 0;
    $recursion = 0;

    $method = function ($times = 0) use (&$method, &$calls, &$callback, &$recursion) {
        $calls++;
        return Recurser::call(
            function () use ($times, $method, &$callback) {
                $callback++;

                $value = [];

                for ($i = 0; $i < $times; $i++) {
                    $value[] = $method();
                }

                return $value;
            },
            // This will only get called the first time that recursion happens.
            function () use (&$recursion) {
                return $recursion++;
            }
        );
    };

    expect($method(0))->toEqual([])
        ->and($calls)->toBe(1)
        ->and($callback)->toBe(1)
        ->and($recursion)->toBe(0)
        ->and($method(3))->toEqual([0, 0, 0])
        ->and($calls)->toBe(5)
        ->and($callback)->toBe(2)
        ->and($recursion)->toBe(1)
        ->and($method(0))->toEqual([])
        ->and($calls)->toBe(6)
        ->and($callback)->toBe(3)
        ->and($recursion)->toBe(1);
});

test('callable responses from on recursion callback will get called when the method recurs', function () {
    $calls = 0;
    $callback = 0;
    $recursion = 0;

    // Increase the recursion value on each recursion, then returns itself
    $onRecursion = function () use (&$recursion, &$onRecursion) {
        $recursion++;

        return $onRecursion;
    };

    $method = function ($times = 0) use (&$method, &$calls, &$callback, &$recursion, &$onRecursion) {
        $calls++;
        return Recurser::call(
            function () use ($times, $method, &$recursion, &$callback) {
                $callback++;

                $value = [];

                for ($i = 0; $i < $times; $i++) {
                    $value[] = $recursion;
                    $method();
                }

                return $value;
            },
            // This will return itself every time it gets called.
            $onRecursion,
        );
    };

    expect($method(0))->toEqual([])
        ->and($calls)->toBe(1)
        ->and($callback)->toBe(1)
        ->and($recursion)->toBe(0)
        ->and($method(3))->toEqual([0, 1, 2])
        ->and($calls)->toBe(5)
        ->and($callback)->toBe(2)
        ->and($recursion)->toBe(3)
        ->and($method(0))->toEqual([])
        ->and($calls)->toBe(6)
        ->and($callback)->toBe(3)
        ->and($recursion)->toBe(3);
});

it('caches any non callable on return value when the method recurs', function () {
    $calls = 0;
    $callback = 0;
    $recursion = 0;

    // Each time it's called this will increase the recursion value towards the next even number, then stop there.
    $onRecursion = function () use (&$recursion, &$onRecursion) {
        return ++$recursion % 2 ? $onRecursion : $recursion;
    };

    $method = function ($times = 0) use (&$method, &$calls, &$callback, &$recursion, &$onRecursion) {
        $calls++;
        return Recurser::call(
            function () use ($times, $method, &$recursion, &$callback) {
                $callback++;

                $value = [];

                for ($i = 0; $i < $times; $i++) {
                    $value[] = $recursion;
                    $method();
                }

                return $value;
            },
            // This will return itself every other time that it gets called.
            $onRecursion,
        );
    };

    expect($method(0))->toEqual([])
        ->and($calls)->toBe(1)
        ->and($callback)->toBe(1)
        ->and($recursion)->toBe(0)
        ->and($method(3))->toEqual([0, 1, 2])
        ->and($calls)->toBe(5)
        ->and($callback)->toBe(2)
        ->and($recursion)->toBe(2)
        ->and($method(0))->toEqual([])
        ->and($calls)->toBe(6)
        ->and($callback)->toBe(3)
        ->and($recursion)->toBe(2)
        ->and($method(8))->toEqual([2, 3, 4, 4, 4, 4, 4, 4])
        ->and($calls)->toBe(15)
        ->and($callback)->toBe(4)
        ->and($recursion)->toBe(4);
});

it('links recursion to a specific instance of a class until it steps out of the call stack', function () {
    $one = new Model(['id' => 1]);
    $two = new Model(['id' => 2]);
    $three = new Model(['id' => 3]);

    $one->setRelation('children', [$two, $three]);
    $two->setRelation('parent', $one);
    $two->setRelation('siblings', [$three]);
    $three->setRelation('parent', $one);
    $three->setRelation('siblings', [$two]);

    expect($one->toArray())
        ->toEqual([
            'id' => 1,
            'children' => [
                [
                    'id' => 2,
                    'parent' => ['id' => 1],
                    'siblings' => [
                        ['id' => 3, 'parent' => ['id' => 1], 'siblings' => [['id' => 2]]]
                    ],
                ],
                [
                    'id' => 3,
                    'parent' => ['id' => 1],
                    'siblings' => [
                        ['id' => 2, 'parent' => ['id' => 1], 'siblings' => [['id' => 3]]]
                    ],
                ],
            ],
        ]);

    $one->setRelation('self', $one);
    $two->setRelation('self', $two);
    $three->setRelation('self', $three);

    expect($one->toArray())
        ->toEqual([
            'id' => 1,
            'children' => [
                [
                    'id' => 2,
                    'parent' => ['id' => 1],
                    'siblings' => [
                        ['id' => 3, 'parent' => ['id' => 1], 'siblings' => [['id' => 2]], 'self' => ['id' => 3]]
                    ],
                    'self' => ['id' => 2],
                ],
                [
                    'id' => 3,
                    'parent' => ['id' => 1],
                    'siblings' => [
                        ['id' => 2, 'parent' => ['id' => 1], 'siblings' => [['id' => 3]], 'self' => ['id' => 2]]
                    ],
                    'self' => ['id' => 3],
                ],
            ],
            'self' => ['id' => 1],
        ]);

    $one->foo = 'bar';
    $two->bing = 'bang';
    $three->fizz = 'buzz';

    expect($one->toArray())
        ->toEqual([
            'id' => 1,
            'foo' => 'bar',
            'children' => [
                [
                    'id' => 2,
                    'bing' => 'bang',
                    'parent' => ['id' => 1, 'foo' => 'bar'],
                    'siblings' => [
                        [
                            'id' => 3,
                            'fizz' => 'buzz',
                            'parent' => ['id' => 1, 'foo' => 'bar'],
                            'siblings' => [['id' => 2, 'bing' => 'bang']],
                            'self' => ['id' => 3, 'fizz' => 'buzz'],
                        ]
                    ],
                    'self' => ['id' => 2, 'bing' => 'bang'],
                ],
                [
                    'id' => 3,
                    'fizz' => 'buzz',
                    'parent' => ['id' => 1, 'foo' => 'bar'],
                    'siblings' => [
                        [
                            'id' => 2,
                            'bing' => 'bang',
                            'parent' => ['id' => 1, 'foo' => 'bar'],
                            'siblings' => [['id' => 3, 'fizz' => 'buzz']],
                            'self' => ['id' => 2, 'bing' => 'bang'],
                        ]
                    ],
                    'self' => ['id' => 3, 'fizz' => 'buzz'],
                ],
            ],
            'self' => ['id' => 1, 'foo' => 'bar'],
        ]);
});

it('links recursion to a specific instance of an object', function () {
    $one = new Model(['id' => 1]);
    $two = new Model(['id' => 2]);
    $three = new Model(['id' => 3]);

    $one->setRelation('children', [$two, $three]);
    $two->setRelation('parent', $one);
    $two->setRelation('siblings', [$three]);
    $three->setRelation('parent', $one);
    $three->setRelation('siblings', [$two]);

    expect($one->toArray($one))
        ->toEqual([
            'id' => 1,
            'children' => [
                ['id' => 1],
                ['id' => 1],
            ],
        ])
        ->and($two->toArray($one))
        ->toEqual([
            'id' => 2,
            'parent' => ['id' => 2],
            'siblings' => [
                ['id' => 2],
            ],
        ]);
});

it('links recursion to a specific instance of an object with a signature', function () {
    $one = new Model(['id' => 1]);
    $two = new Model(['id' => 2]);
    $three = new Model(['id' => 3]);

    $one->setRelation('children', [$two, $three]);
    $two->setRelation('parent', $one);
    $two->setRelation('siblings', [$three]);
    $three->setRelation('parent', $one);
    $three->setRelation('siblings', [$two]);

    expect($one->toArray($one, true))
        ->toEqual([
            'id' => 1,
            'children' => [
                [
                    'id' => 2,
                    'parent' => ['id' => 1],
                    'siblings' => [
                        [
                            'id' => 3,
                            'parent' => ['id' => 1],
                            'siblings' => [
                                ['id' => 2],
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 3,
                    'parent' => ['id' => 1],
                    'siblings' => [
                        [
                            'id' => 2,
                            'parent' => ['id' => 1],
                            'siblings' => [
                                ['id' => 3],
                            ],
                        ],
                    ],
                ]
            ],
        ]);
});
