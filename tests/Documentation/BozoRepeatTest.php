<?php

function bozo_repeat(string $repeat = ''): string
{
    return RecursionGuard\Recurser::call(
    // The callback that we want to call
        fn () => bozo_repeat() . ' : ' . bozo_repeat(),
        // What to return if this function is called recursively
        $repeat ?: 'bozo(' . random_int(0, 100) . ')'
    );
}

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

it('resolves the random integer again for each call', function () {
    $one = bozo_repeat();
    $two = bozo_repeat();

    expect($one)->toMatch('/^bozo\(\d+\) : bozo\(\d+\)$/')
        ->not->toEqual($two)
        ->and($two)->toMatch('/^bozo\(\d+\) : bozo\(\d+\)$/');

    $oneParts = explode(' : ', $one);
    $twoParts = explode(' : ', $two);;

    expect($oneParts[0])->toEqual($oneParts[1])
        ->not->toEqual($twoParts[0])
        ->not->toEqual($twoParts[1])
        ->and($twoParts[0])->toEqual($twoParts[1]);
});
