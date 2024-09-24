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

test('it should make from valid trace', function ($from, $expected) {
    $context = RecursionContext::fromTrace($from);

    expect($context->file)->toBe($expected['file'])
        ->and($context->line)->toBe($expected['line'])
        ->and($context->function)->toBe($expected['function'])
        ->and($context->class)->toBe($expected['class'])
        ->and($context->object)->toEqual($expected['object'])
        ->and($context->signature())->toBe($expected['signature']);
})->with([
    'single array' => [
        [['file' => 'foo.php', 'line' => 42, 'function' => 'bar', 'class' => 'baz', 'object' => (object) []]],
        [
            'file' => 'foo.php',
            'line' => 42,
            'function' => '',
            'class' => '',
            'object' => null,
            'signature' => 'foo.php:42',
        ],
    ],
    'double entry trace' => [
        [
            ['file' => 'foo.php', 'line' => 42, 'function' => 'bar', 'class' => 'baz', 'object' => (object) []],
            ['file' => 'foo.php', 'line' => 42, 'function' => 'bar', 'class' => 'baz', 'object' => (object) []],
        ],
        [
            'file' => 'foo.php',
            'line' => 42,
            'function' => 'bar',
            'class' => 'baz',
            'object' => (object) [],
            'signature' => 'foo.php:baz@bar',
        ],
    ],
]);

test('it should throw an exception when trying to make from invalid trace', function ($from) {
    RecursionContext::fromTrace($from);
})->throws(InvalidContextException::class)->with([
    'empty array' => [[]],
    'array of empty arrays' => [[[], [], []]],
    'invalid backtrace' => [[['foo' => 'bar'], ['foo' => 'bar'], ['foo' => 'bar']]],
]);

test('it should make from callable functions', function (callable $from) {
    $context = RecursionContext::fromCallable($from);

    $reflector = new ReflectionFunction($from);

    $name = $reflector->getName() === '{closure}' ? (string)$reflector : $reflector->getName();

    expect($context->file)->toBe($reflector->getFileName() ?: '')
        ->and($context->line)->toBe($reflector->getStartLine() ?: 0)
        ->and($context->function)->toBe($name)
        ->and($context->class)->toBe($reflector->getClosureScopeClass()?->getName() ?: '')
        ->and($context->object)->toEqual($reflector->getClosureThis())
        ->and($context->signature())->toBe(sprintf(
            '%s:%s',
            $reflector->getFileName(),
            ($reflector->getClosureScopeClass() ? $reflector->getClosureScopeClass()->getName() . '@' : '')
                . ($name ?: $reflector->getStartLine() ?: 0),
        ));
})->with([
    'short closure' => [fn () => 'foo'],
    'long closure' => [function () {
        return'foo';
    }],
    'callable string' => ['rand'],
    'first class callable' => [rand(...)],
]);

test('it should make from callable array', function (callable $from) {
    $context = RecursionContext::fromCallable($from);

    $class = new ReflectionClass($from[0]);
    $method = $class->getMethod($from[1]);

    expect($context->file)->toBe($class->getFileName() ?: '')
        ->and($context->line)->toBe($method->getStartLine() ?: 0)
        ->and($context->function)->toBe($method->getName())
        ->and($context->class)->toBe($class->getName())
        ->and($context->object)->toEqual(is_object($from[0]) ? $from[0] : null)
        ->and($context->signature())->toBe(sprintf(
            '%s:%s@%s',
	    $class->getFileName(),
	    $class->getName(),
	    $method->getName(),
        ));
})->with([
    'class string' => [[RecursionContext::class, 'make']],
    'object method' => [[new RecursionContext, 'signature']],
    'object static method' => [[new RecursionContext, 'make']],
    'invokeable class' => [[new class {
	public function __invoke(): void
	{
	    //
	}
    }, '__invoke']],
    'built-in string' => [['DateTime', 'createFromFormat']],
    'built-in object' => [[new \DateTime, 'format']],
]);

test('it should make from invokable class', function () {
    $from = new class {
        public function __invoke(): void
	{
	    //
        }
    };

    $context = RecursionContext::fromCallable($from);

    $class = new ReflectionClass($from);
    $method = $class->getMethod('__invoke');

    expect($context->file)->toBe(__FILE__)
        ->and($context->line)->toBe($method->getStartLine())
        ->and($context->function)->toBe('__invoke')
        ->and($context->class)->toBe($class->getName())
        ->and($context->object)->toEqual($from)
        ->and($context->signature())->toBe(sprintf(
            '%s:%s@%s',
	    __FILE__,
	    $class->getName(),
	    '__invoke',
        ));
});

