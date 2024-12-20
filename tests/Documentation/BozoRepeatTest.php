<?php

declare(strict_types=1);

use function Tests\Support\Documentation\BozoRepeat\bozo_repeat;

it('repeats a random integer', function () {
    $repeat = bozo_repeat();

    expect($repeat)->toMatch('/^bozo\(\d+\) : bozo\(\d+\)$/');

    $parts = explode(' : ', $repeat);

    expect($parts[0])->toEqual($parts[1]);
});

it('repeats a string', function () {
    $repeat = bozo_repeat('foo');

    expect($repeat)->toEqual('foo : foo');
});

it('can resolve different random integers for each call', function () {
    $calls = [
        bozo_repeat(),
        bozo_repeat(),
        bozo_repeat(),
        bozo_repeat(),
        bozo_repeat(),
        bozo_repeat(),
        bozo_repeat(),
        bozo_repeat(),
        bozo_repeat(),
        bozo_repeat(),
    ];

    expect($calls)->each->toMatch('/^bozo\(\d+\) : bozo\(\d+\)$/')
        // each call should have the same random integer for both parts
        ->and(array_map(fn ($call) => array_unique(explode(' : ', $call)), $calls))->each->toHaveCount(1)
        // we might get some repeats, but we should always have more than one unique string
        ->and(count(array_unique($calls)))->toBeGreaterThan(1);
});
