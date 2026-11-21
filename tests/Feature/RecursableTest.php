<?php

declare(strict_types=1);

use RecursionGuard\Exception\RecursionException;
use RecursionGuard\Recursable;
use Tests\Support\SetsState;

covers(Recursable::class, RecursionException::class);

it('hashes signature on creation', function () {
    $signature = random_bytes(16);

    $recursable = new Recursable(fn () => null, signature: $signature);

    expect($recursable->signature())->toBe($signature)
        ->and($recursable->hash())->toBe(hash('xxh128', $signature));
})->repeat(10);

it('reports state correctly', function (bool $started, int $stackDepth) {
    $recursable = (new class (fn () => null) extends Recursable {
        use SetsState;
    })->state(started: $started, stackDepth: $stackDepth);

    expect($recursable->started())->toBe($started)
        ->and($recursable->stackDepth())->toBe($stackDepth)
        ->and($recursable->running())->toBe($started && $stackDepth >= 1)
        ->and($recursable->recursing())->toBe($started && $stackDepth >= 2)
        ->and($recursable->overflown())->toBe($stackDepth < 0)
        ->and($recursable->finished())->toBe($started && $stackDepth === 0);
})->with([
    'not started' => [false, 0],
    'running' => [true, 1],
    'recursing' => [true, 2],
    'finished' => [true, 0],
    'impossible state: stack depth before starting' => [false, 1],
    'impossible state: high depth' => [true, 3],
    'impossible state: negative depth' => [true, -1],
]);

it('uses closure callable for callback', function (callable $callable) {
    $recursable = new Recursable($callable);

    expect($recursable->callback())
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
    $expected = \Closure::fromCallable($callable);
    $actualReflection = new ReflectionFunction($recursable->callback());
    $expectedReflection = new ReflectionFunction($expected);

    expect($recursable->callback())->toBeInstanceOf(\Closure::class)
        ->and([
            $actualReflection->getClosureScopeClass()?->getName(),
            $actualReflection->getClosureThis(),
            $actualReflection->getName(),
        ])
        ->toEqualCanonicalizing([
            $expectedReflection->getClosureScopeClass()?->getName(),
            $expectedReflection->getClosureThis(),
            $expectedReflection->getName(),
        ])
        ->and($recursable->callback())
        ->toBeInstanceOf(\Closure::class)
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

    expect($recursable->signature())->toBe($signature)
        ->and($recursable->hash())->toBe(hash('xxh128', $signature))
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

    expect($recursable->signature())->toBe($signature)
        ->and($recursable->hash())->toBe(hash('xxh128', $signature))
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

    expect($recursable->signature())->toBe($signature)
        ->and($recursable->hash())->toBe(hash('xxh128', $signature))
        ->and($recursable->object())->toBeNull();
});

it('sets object only once', function () {
    $recursable = new Recursable(fn () => null);
    $one = (object) [];
    $two = (object) [];

    expect($recursable->object())->toBeNull()
        ->and($recursable->forObject(null)->object())->toBeNull()
        ->and($recursable->forObject($one)->object())->toBe($one)
        ->and($recursable->forObject(null)->object())->toBe($one)
        ->and($recursable->forObject($two)->object())->toBe($one);
});

it('allows overriding return value', function () {
    $recursable = new Recursable(fn () => null);
    $callback = fn () => 'bar';

    expect($recursable->recurseWith())->toBeNull()
        ->and($recursable->andReturn('foo')->recurseWith())->toBe('foo')
        ->and($recursable->andReturn(null)->recurseWith())->toBeNull()
        ->and($recursable->andReturn($callback)->recurseWith())->toBe($callback)
        ->and($recursable->andReturn('bar')->recurseWith())->toBe('bar');
});

it('resolves callback once', function ($method) {
    $recursable = new Recursable(fn () => 'foo');

    expect($recursable->started())->toBeFalse()
        ->and($recursable->running())->toBeFalse()
        ->and($recursable->recursing())->toBeFalse()
        ->and($recursable->finished())->toBeFalse()
        ->and($recursable->stackDepth())->toBe(0)
        ->and($recursable->$method())->toBe('foo')
        ->and($recursable->started())->toBeTrue()
        ->and($recursable->running())->toBeFalse()
        ->and($recursable->recursing())->toBeFalse()
        ->and($recursable->finished())->toBeTrue()
        ->and($recursable->stackDepth())->toBe(0)
        ->and(fn () => $recursable())->toThrow(
            RecursionException::class,
            sprintf(RecursionException::MESSAGE_FINISHED, $recursable->signature()),
        );
})->with([
    'resolve',
    '__invoke',
]);

it('resolves on recursion value if resolved recursively', function ($method) {
    $recursable = new Recursable(function () use (&$recursable, $method) {
        expect($recursable->started())->toBeTrue()
            ->and($recursable->running())->toBeTrue()
            ->and($recursable->recursing())->toBeFalse()
            ->and($recursable->finished())->toBeFalse()
            ->and($recursable->started())->toBeTrue()
            ->and($recursable->stackDepth())->toBe(1);

        return $recursable->$method();
    }, 'bar');

    expect($recursable->started())->toBeFalse()
        ->and($recursable->running())->toBeFalse()
        ->and($recursable->recursing())->toBeFalse()
        ->and($recursable->finished())->toBeFalse()
        ->and($recursable->started())->toBeFalse()
        ->and($recursable->stackDepth())->toBe(0)
        ->and($recursable->$method())->toBe('bar')
        ->and($recursable->started())->toBeTrue()
        ->and($recursable->running())->toBeFalse()
        ->and($recursable->recursing())->toBeFalse()
        ->and($recursable->finished())->toBeTrue()
        ->and($recursable->started())->toBeTrue()
        ->and($recursable->stackDepth())->toBe(0)
        ->and(fn () => $recursable->$method())->toThrow(
            RecursionException::class,
            sprintf(RecursionException::MESSAGE_FINISHED, $recursable->signature()),
        );
})->with([
    'resolve',
    '__invoke',
]);

it('calls and caches on recursion callable if resolved recursively', function ($method) {
    $recursable = new Recursable(
        function () use (&$recursable, $method) {
            expect($recursable->started())->toBeTrue()
                ->and($recursable->running())->toBeTrue()
                ->and($recursable->recursing())->toBeFalse()
                ->and($recursable->finished())->toBeFalse()
                ->and($recursable->started())->toBeTrue()
                ->and($recursable->stackDepth())->toBe(1);

            $result = $recursable->$method();

            expect($result)->toBe('baz')
                ->and($recursable->recurseWith())->toBe('baz')
                ->and($recursable->stackDepth())->toBe(1)
                ->and($recursable->recursing())->toBeFalse()
                ->and($recursable->$method())->toBe($result);

            return $result;
        },
        function () use (&$recursable) {
            expect($recursable->started())->toBeTrue()
                ->and($recursable->running())->toBeTrue()
                ->and($recursable->recursing())->toBeTrue()
                ->and($recursable->finished())->toBeFalse()
                ->and($recursable->started())->toBeTrue()
                ->and($recursable->stackDepth())->toBe(2);

            return 'baz';
        }
    );

    expect($recursable->started())->toBeFalse()
        ->and($recursable->running())->toBeFalse()
        ->and($recursable->recursing())->toBeFalse()
        ->and($recursable->finished())->toBeFalse()
        ->and($recursable->started())->toBeFalse()
        ->and($recursable->stackDepth())->toBe(0)
        ->and($recursable->$method())->toBe('baz')
        ->and($recursable->started())->toBeTrue()
        ->and($recursable->running())->toBeFalse()
        ->and($recursable->recursing())->toBeFalse()
        ->and($recursable->finished())->toBeTrue()
        ->and($recursable->started())->toBeTrue()
        ->and($recursable->stackDepth())->toBe(0)
        ->and(fn () => $recursable->$method())->toThrow(
            RecursionException::class,
            sprintf(RecursionException::MESSAGE_FINISHED, $recursable->signature())
        );
})->with([
    'resolve',
    '__invoke',
]);

it('allows read access to properties', function () {
    $callback = fn () => 'foo';
    $object = new stdClass();

    $recursable = (new Recursable($callback, 'bar', 'baz'))->forObject($object);

    expect($recursable->signature())->toBe('baz')
        ->and($recursable->hash())->toBe(hash('xxh128', 'baz'))
        ->and($recursable->callback())->toBe($callback)
        ->and($recursable->recurseWith())->toBe('bar')
        ->and($recursable->object())->toBe($object)
        ->and($recursable->started())->toBeFalse()
        ->and($recursable->stackDepth())->toBe(0);
});

it('rejects read access to properties that do not exist', function ($method) {
    $recursable = new Recursable(fn () => 'foo');

    expect(fn () => $recursable->$method())->toThrow(
        \BadMethodCallException::class,
        sprintf('Call to undefined method %s::%s()', Recursable::class, $method)
    );
})->with([
    'unknown',
    'foo',
    '0',
]);
