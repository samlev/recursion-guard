<?php

class Repeater
{
    public function __construct(
        public readonly int $times,
    ) {
        //
    }

    /**
     * @param string|callable(): string $repeat
     * @param string $default
     * @return string
     */
    public function __invoke(string|callable $repeat, string $default): string
    {
        return RecursionGuard\Recurser::call(
            fn () => sprintf('%d: [%s]', $this->times, implode(', ', array_fill(0, $this->times, (is_callable($repeat) ? $repeat() : $repeat)))),
            sprintf('%d (default): [%s]', $this->times, implode(', ', array_fill(0, $this->times, $default))),
        );
    }
}

it('repeats a string', function ($times, $value, $default, $expected) {
    $repeat = new Repeater($times);

    expect($repeat($value, $default))->toEqual($expected);
})->with([
    'once' => [1, 'foo', 'bar', '1: [foo]'],
    'twice' => [2, 'foo', 'bar', '2: [foo, foo]'],
    'thrice' => [3, 'foo', 'bar', '3: [foo, foo, foo]'],
]);

it('repeats a callable', function ($times, callable $callable, $default, $expected) {
    $repeat = new Repeater($times);

    expect($repeat($callable, $default))->toEqual($expected);
})->with([
    'once' => [1, fn () => 'bar', 'baz', '1: [bar]'],
    'twice' => [2, fn () => 'bar', 'baz', '2: [bar, bar]'],
    'thrice' => [3, fn () => 'bar', 'baz', '3: [bar, bar, bar]'],
]);

it('repeats with a default on recursion', function ($times, $value, $default, $expected) {
    $repeat = new Repeater($times);

    expect($repeat(fn () => $repeat($value, 'never'), $default))->toEqual($expected);
})->with([
    'once' => [1, 'bing', 'bang', '1: [1 (default): [bang]]'],
    'twice' => [2, 'bing', 'bang', '2: [2 (default): [bang, bang], 2 (default): [bang, bang]]'],
    'thrice' => [
        3,
        'bing',
        'bang',
        '3: [3 (default): [bang, bang, bang], 3 (default): [bang, bang, bang], 3 (default): [bang, bang, bang]]',
    ],
]);

it('it guards recursion per instance', function ($times, $value, $default, $expected) {
    $one = new Repeater($times);
    $two = new Repeater($times);

    expect($one(fn () => $two($value, 'never'), $default))->toEqual($expected);
})->with([
    'once' => [1, 'bing', 'bang', '1: [1: [bing]]'],
    'twice' => [2, 'bing', 'bang', '2: [2: [bing, bing], 2: [bing, bing]]'],
    'thrice' => [
        3,
        'bing',
        'bang',
        '3: [3: [bing, bing, bing], 3: [bing, bing, bing], 3: [bing, bing, bing]]'
    ],
]);
