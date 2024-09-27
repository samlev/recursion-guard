<?php

function through(mixed $value, callable $through): mixed
{
    return RecursionGuard\Recurser::call(fn () => $through($value), $value);
}

function pipe(mixed $value, callable ...$through): mixed
{
    return array_reduce($through, through(...), $value);
}

it('passes a value through a callback', function () {
    expect(through('foo', 'strtoupper'))->toBe('FOO');
});

it('pipes a value through multiple callbacks', function () {
    expect(pipe('foo', strtoupper(...), str_split(...), array_unique(...)))
        ->toBe(['F', 'O']);
});

it('does not recurse if nesting through calls', function () {
    expect(through(through(through('foo', 'strtoupper'), 'str_split'), 'array_unique'))
        ->toBe(['F', 'O']);
});

it('returns the original value on recursion', function () {
    $unique = fn ($value) => pipe($value, strtoupper(...), str_split(...), array_unique(...));

    expect($unique('foo'))->toBe(['F', 'O'])
        ->and(through('foo', $unique))->toBe('foo')
        ->and(pipe('foo', $unique))->toBe('foo');
});
