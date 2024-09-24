<?php

declare(strict_types=1);

use RecursionGuard\Data\RecursionContext;
use RecursionGuard\Exception\InvalidContextException;
use Tests\Support\Stubs\RecursionContextStub;

covers(RecursionContext::class);

test('it should call appropriate make method', function ($from, string $method) {
    $this->spy()->expect(RecursionContextStub::class . '::' . $method, [$from]);

    try {
        RecursionContextStub::make($from);
    } catch (InvalidContextException) {
        //
    }

    $this->spy->assert();
})->with([
    'empty array' => [[], 'fromTrace'],
    'array of empty arrays' => [[[], []], 'fromTrace'],
    'single array' => [[['file' => '']], 'fromTrace'],
    'backtrace array' => [
        [['file' => 'foo.php', 'line' => 1, 'function' => 'bar', 'class' => 'baz', 'object' => (object) []]],
        'fromTrace'
    ],
    'short closure' => [fn () => 'foo', 'fromCallable'],
    'long closure' => [
        function () {
            return 'foo';
        },
        'fromCallable'
    ],
    'callable string' => ['rand', 'fromCallable'],
    'first class callable' => [rand(...), 'fromCallable'],
    'callable array' => [[new RecursionContext(), 'signature'], 'fromCallable'],
]);

test('it should throw an exception when trying to make from invalid trace', function ($from) {
    RecursionContext::fromTrace($from);
})->throws(InvalidContextException::class)->with([
    'empty array' => [[]],
    'array of empty arrays' => [[[], [], []]],
    'invalid backtrace' => [[['foo' => 'bar'], ['foo' => 'bar'], ['foo' => 'bar']]],
]);
