<?php

declare(strict_types=1);

function once(callable $callback)
{
    return RecursionGuard\Recurser::call($callback, 'RECURSION');
}

function twice(callable $callback)
{
    return once($callback) . ', ' . once($callback);
}

function roll_dice()
{
    return random_int(1, 6);
}

function roll_two_dice()
{
    return twice('roll_dice');
}

it('rolls a die', function () {
    $rolls = [
        roll_dice(),
        roll_dice(),
        roll_dice(),
        roll_dice(),
        roll_dice(),
        roll_dice(),
        roll_dice(),
        roll_dice(),
        roll_dice(),
        roll_dice(),
    ];

    expect($rolls)->toHaveCount(10)
        ->each->toBeIn([1, 2, 3, 4, 5, 6])
        ->and(array_unique($rolls))->not->toHaveCount(1);
});

it('rolls a die through once', function () {
    $rolls = [
        once('roll_dice'),
        once('roll_dice'),
        once('roll_dice'),
        once('roll_dice'),
        once('roll_dice'),
        once('roll_dice'),
        once('roll_dice'),
        once('roll_dice'),
        once('roll_dice'),
        once('roll_dice'),
    ];

    expect($rolls)->toHaveCount(10)
        ->each->toBeIn([1, 2, 3, 4, 5, 6])
        ->and(array_unique($rolls))->not->toHaveCount(1);
});

it('rolls a die twice through twice', function () {
    $rolls = [
        twice('roll_dice'),
        twice('roll_dice'),
        twice('roll_dice'),
        twice('roll_dice'),
        twice('roll_dice'),
    ];

    expect($rolls)->toHaveCount(5)
        ->each->toMatch('/^[1-6], [1-6]$/')
        ->and(array_unique($rolls))->not->toHaveCount(1);

    $parts = array_map(fn ($roll) => explode(', ', $roll), $rolls);

    expect($parts)->toHaveCount(5)
        ->each->toHaveCount(2);

    $unique = array_filter(array_map('array_unique', $parts), fn ($part) => count($part) === 2);

    expect(count($unique))->toBeGreaterThan(0);
});

it('rolls two dice', function () {
    $rolls = [
        roll_two_dice(),
        roll_two_dice(),
        roll_two_dice(),
        roll_two_dice(),
        roll_two_dice(),
    ];

    expect($rolls)->toHaveCount(5)
        ->each->toMatch('/^[1-6], [1-6]$/')
        ->and(array_unique($rolls))->not->toHaveCount(1);

    $parts = array_map(fn ($roll) => explode(', ', $roll), $rolls);

    expect($parts)->toHaveCount(5)
        ->each->toHaveCount(2);

    $unique = array_filter(array_map('array_unique', $parts), fn ($part) => count($part) === 2);

    expect(count($unique))->toBeGreaterThan(0);
});

it('hits recursion guard on roll two dice through once', function () {
    $rolls = [
        once('roll_two_dice'),
        once('roll_two_dice'),
        once('roll_two_dice'),
        once('roll_two_dice'),
        once('roll_two_dice'),
    ];

    expect($rolls)->toHaveCount(5)
        ->each->toEqual('RECURSION, RECURSION');
});

it('hits recursion guard on roll two dice through twice', function () {
    $rolls = [
        twice('roll_two_dice'),
        twice('roll_two_dice'),
        twice('roll_two_dice'),
        twice('roll_two_dice'),
        twice('roll_two_dice'),
    ];

    expect($rolls)->toHaveCount(5)
        ->each->toEqual('RECURSION, RECURSION, RECURSION, RECURSION');
});
