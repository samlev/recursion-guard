<?php

declare(strict_types=1);

use RecursionGuard\Exception\RecursionException;
use RecursionGuard\Recursable;
use Tests\Support\Stubs\RecursableStub;

covers(Recursable::class);

it('hashes signature', function () {
    $signature = random_bytes(16);

    expect(RecursableStub::expose_hashSignature($signature))
        ->toBe(hash('xxh128', $signature));
})->repeat(10);

it('hashes signature on creation', function () {
    $signature = random_bytes(16);

    $recursable = new Recursable(fn () => null, signature: $signature);

    expect($recursable->signature)->toBe($signature)
        ->and($recursable->hash)->toBe(hash('xxh128', $signature));
})->repeat(10);

it('reports state correctly', function ($state, $started, $running, $recursing, $finished) {
    $recursable = (new RecursableStub(fn () => null))->state(...$state);

    expect($recursable->started())->toBe($started)
        ->and($recursable->running())->toBe($running)
        ->and($recursable->recursing())->toBe($recursing)
        ->and($recursable->finished())->toBe($finished)
        ->and($recursable->expose_started)->toBe($started)
        ->and($recursable->expose_stackDepth)->toBe($state['stackDepth'] ?? 0);
})->with([
    'standard' => [[], false, false, false, false],
    'running' => [['started' => true, 'stackDepth' => 1], true, true, false, false],
    'recursing' => [['started' => true, 'stackDepth' => 2], true, true, true, false],
    'finished' => [['started' => true], true, false, false, true],
    'impossible state: stack depth before starting' => [['stackDepth' => 1], false, false, false, false],
    'impossible state: high depth' => [['started' => true, 'stackDepth' => 3], true, true, true, false],
]);

it('uses closure callable for callback', function (callable $callable) {
    $recursable = new Recursable($callable);

    expect($recursable->callback)
        ->toBeInstanceOf(\Closure::class)
        ->toBe($callable);
})->with([
    'short closure' => [fn () => 'foo'],
    'long closure' => [function () {
        return'foo';
    }],
    'first class callable' => [rand(...)],
    'made closure' => [\Closure::fromCallable('rand')],
]);

it('wraps non-closure callable in closure for callback', function (callable $callable) {
    $recursable = new Recursable($callable);

    expect($recursable->callback)
        ->toBeInstanceOf(\Closure::class)
        ->toEqual($callable(...))
        ->not->toBe($callable);
})->with([
    'callable string' => ['rand'],
    'static callable array' => [[\DateTime::class, 'createFromFormat']],
    'instance callable array' => [[new \DateTime(), 'format']],
    'static method on instance callable array' => [[new \DateTime(), 'createFromFormat']],
    'invokable class' => [new class () {
        public function __invoke(): string
        {
            return 'foo';
        }
    }],
]);

it('generates signature from callback function', function (callable $callable) {
    $recursable = new Recursable($callable);

    $reflector = new ReflectionFunction($callable);

    $file = $reflector->getFileName() ?: '';
    $function = $reflector->getName() === '{closure}' ? (string)$reflector : $reflector->getName();
    $class = $reflector->getClosureScopeClass()?->getName() ?: '';
    $line = $reflector->getStartLine() ?: 0;
    $signature = sprintf('%s:%s', $file, ($class ? ($class . '@') : '') . ($function ?: $line));

    expect($recursable->signature)->toBe($signature)
        ->and($recursable->hash)->toBe(hash('xxh128', $signature))
        ->and($recursable->object())->toBeNull();
})->with([
    'short closure' => [fn () => 'foo'],
    'long closure' => [function () {
        return'foo';
    }],
    'callable string' => ['rand'],
    'first class callable' => [rand(...)],
]);

it('generates signature from callable array', function (callable $callable) {
    $recursable = new Recursable($callable);

    $class = new ReflectionClass($callable[0]);
    $method = $class->getMethod($callable[1]);

    $file = $class->getFileName() ?: '';
    $function = $method->getName();
    $class = $class->getName();
    $line = $method->getStartLine() ?: 0;
    $signature = sprintf('%s:%s', $file, ($class ? ($class . '@') : '') . ($function ?: $line));

    expect($recursable->signature)->toBe($signature)
        ->and($recursable->hash)->toBe(hash('xxh128', $signature))
        ->and($recursable->object())->toBeNull();
})->with([
    'static callable array' => [[\DateTime::class, 'createFromFormat']],
    'instance callable array' => [[new \DateTime(), 'format']],
    'static method on instance callable array' => [[new \DateTime(), 'createFromFormat']],
    'invokable class' => [[
        new class () {
            public function __invoke(): string
            {
                return 'foo';
            }
        },
        '__invoke',
    ]],
]);

it('generates signature from invokable class', function () {
    $callable = new class () {
        public function __invoke(): string
        {
            return 'foo';
        }
    };

    $recursable = new Recursable($callable);

    $class = new ReflectionClass($callable);
    $method = $class->getMethod('__invoke');

    $file = $class->getFileName() ?: '';
    $function = $method->getName();
    $class = $class->getName();
    $line = $method->getStartLine() ?: 0;
    $signature = sprintf('%s:%s', $file, ($class ? ($class . '@') : '') . ($function ?: $line));

    expect($recursable->signature)->toBe($signature)
        ->and($recursable->hash)->toBe(hash('xxh128', $signature))
        ->and($recursable->object())->toBeNull();
});

test('sets object only once', function () {
    $recursable = new RecursableStub(fn () => null);
    $one = (object) [];
    $two = (object) [];

    expect($recursable->expose_object)->toBeNull()
        ->and($recursable->object())->toBeNull();

    $recursable->forObject(null);

    expect($recursable->expose_object)->toBeNull()
        ->and($recursable->object())->toBeNull();

    $recursable->forObject($one);

    expect($recursable->expose_object)->toBe($one)
        ->and($recursable->object())->toBe($one);

    $recursable->forObject(null);

    expect($recursable->expose_object)->toBe($one)
        ->and($recursable->object())->toBe($one);

    $recursable->forObject($two);

    expect($recursable->expose_object)->toBe($one)
        ->and($recursable->object())->toBe($one);
});

test('allows overriding return value', function () {
    $recursable = new RecursableStub(fn () => null);
    $two = fn () => 'bar';

    expect($recursable->expose_recurseWith)->toBeNull();

    $recursable->andReturn('foo');

    expect($recursable->expose_recurseWith)->toBe('foo');

    $recursable->andReturn(null);

    expect($recursable->expose_recurseWith)->toBeNull();

    $recursable->andReturn($two);

    expect($recursable->expose_recurseWith)->toBe($two);

    $recursable->andReturn('bar');

    expect($recursable->expose_recurseWith)->toBe('bar');
});

test('resolves callback once', function ($method) {
    $recursable = new RecursableStub(fn () => 'foo');

    expect($recursable->started())->toBeFalse()
        ->and($recursable->running())->toBeFalse()
        ->and($recursable->recursing())->toBeFalse()
        ->and($recursable->finished())->toBeFalse()
        ->and($recursable->expose_started)->toBeFalse()
        ->and($recursable->expose_stackDepth)->toBe(0)
        ->and($recursable->$method())->toBe('foo')
        ->and($recursable->started())->toBeTrue()
        ->and($recursable->running())->toBeFalse()
        ->and($recursable->recursing())->toBeFalse()
        ->and($recursable->finished())->toBeTrue()
        ->and($recursable->expose_started)->toBeTrue()
        ->and($recursable->expose_stackDepth)->toBe(0)
        ->and(fn () => $recursable->$method())->toThrow(RecursionException::class);
})->with([
    'resolve',
    '__invoke',
]);

test('resolves on recursion value if resolved recursively', function ($method) {
    $recursable = new RecursableStub(function () use (&$recursable, $method) {
        expect($recursable->started())->toBeTrue()
            ->and($recursable->running())->toBeTrue()
            ->and($recursable->recursing())->toBeFalse()
            ->and($recursable->finished())->toBeFalse()
            ->and($recursable->expose_started)->toBeTrue()
            ->and($recursable->expose_stackDepth)->toBe(1);

        return $recursable->$method();
    }, 'bar');

    expect($recursable->started())->toBeFalse()
        ->and($recursable->running())->toBeFalse()
        ->and($recursable->recursing())->toBeFalse()
        ->and($recursable->finished())->toBeFalse()
        ->and($recursable->expose_started)->toBeFalse()
        ->and($recursable->expose_stackDepth)->toBe(0)
        ->and($recursable->$method())->toBe('bar')
        ->and($recursable->started())->toBeTrue()
        ->and($recursable->running())->toBeFalse()
        ->and($recursable->recursing())->toBeFalse()
        ->and($recursable->finished())->toBeTrue()
        ->and($recursable->expose_started)->toBeTrue()
        ->and($recursable->expose_stackDepth)->toBe(0)
        ->and(fn () => $recursable->$method())->toThrow(RecursionException::class);
})->with([
    'resolve',
    '__invoke',
]);

test('if calls and caches on recursion callable if resolved recursively', function ($method) {
    $recursable = new RecursableStub(
        function () use (&$recursable, $method) {
            expect($recursable->started())->toBeTrue()
                ->and($recursable->running())->toBeTrue()
                ->and($recursable->recursing())->toBeFalse()
                ->and($recursable->finished())->toBeFalse()
                ->and($recursable->expose_started)->toBeTrue()
                ->and($recursable->expose_stackDepth)->toBe(1);

            $result = $recursable->$method();

            expect($result)->toBe('baz')
                ->and($recursable->expose_recurseWith)->toBe('baz')
                ->and($recursable->expose_stackDepth)->toBe(1)
                ->and($recursable->recursing())->toBeFalse()
                ->and($recursable->$method())->toBe($result);

            return $result;
        },
        function () use (&$recursable) {
            expect($recursable->started())->toBeTrue()
                ->and($recursable->running())->toBeTrue()
                ->and($recursable->recursing())->toBeTrue()
                ->and($recursable->finished())->toBeFalse()
                ->and($recursable->expose_started)->toBeTrue()
                ->and($recursable->expose_stackDepth)->toBe(2);

            return 'baz';
        }
    );

    expect($recursable->started())->toBeFalse()
        ->and($recursable->running())->toBeFalse()
        ->and($recursable->recursing())->toBeFalse()
        ->and($recursable->finished())->toBeFalse()
        ->and($recursable->expose_started)->toBeFalse()
        ->and($recursable->expose_stackDepth)->toBe(0)
        ->and($recursable->$method())->toBe('baz')
        ->and($recursable->started())->toBeTrue()
        ->and($recursable->running())->toBeFalse()
        ->and($recursable->recursing())->toBeFalse()
        ->and($recursable->finished())->toBeTrue()
        ->and($recursable->expose_started)->toBeTrue()
        ->and($recursable->expose_stackDepth)->toBe(0)
        ->and(fn () => $recursable->$method())->toThrow(RecursionException::class);
})->with([
    'resolve',
    '__invoke',
]);
