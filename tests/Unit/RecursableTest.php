<?php

declare(strict_types=1);

use RecursionGuard\Exception\RecursionException;
use RecursionGuard\Recursable;
use Tests\Support\Stubs\RecursableStub;

covers(Recursable::class);

test('it hashes signature', function () {
    $signature = random_bytes(16);

    expect(RecursableStub::expose_hashSignature($signature))
        ->toBe(hash('xxh128', $signature));
})->repeat(10);

test('it hashes signature on new', function () {
    $signature = random_bytes(16);

    $recursable = new Recursable(fn () => null, signature: $signature);

    expect($recursable->signature)->toBe($signature)
        ->and($recursable->hash)->toBe(hash('xxh128', $signature));
})->repeat(10);

test('it uses closure as callback directly on new', function (callable $callable) {
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

test('it wraps non-closure callback in closure on new', function (callable $callable) {
    $recursable = new Recursable($callable);

    expect($recursable->callback)
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

test('it generates signature from callback function on new', function (callable $callable) {
    $recursable = new Recursable($callable);

    $reflector = new ReflectionFunction($callable);

    $file = $reflector->getFileName() ?: '';
    $function = $reflector->getName() === '{closure}' ? (string)$reflector : $reflector->getName();
    $class = $reflector->getClosureScopeClass()?->getName() ?: '';
    $line = $reflector->getStartLine() ?: 0;
    $signature = sprintf('%s:%s', $file, ($class ? ($class . '@') : '') . ($function ?: $line));

    expect($recursable->signature)->toBe($signature)
        ->and($recursable->hash)->toBe(hash('xxh128', $signature));
})->with([
    'short closure' => [fn () => 'foo'],
    'long closure' => [function () {
        return'foo';
    }],
    'callable string' => ['rand'],
    'first class callable' => [rand(...)],
]);

test('it generates signature from callable array on new', function (callable $callable) {
    $recursable = new Recursable($callable);

    $class = new ReflectionClass($callable[0]);
    $method = $class->getMethod($callable[1]);

    $file = $class->getFileName() ?: '';
    $function = $method->getName();
    $class = $class->getName();
    $line = $method->getStartLine() ?: 0;
    $signature = sprintf('%s:%s', $file, ($class ? ($class . '@') : '') . ($function ?: $line));

    expect($recursable->signature)->toBe($signature)
        ->and($recursable->hash)->toBe(hash('xxh128', $signature));
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

test('it generates signature from invokable class on new', function () {
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
        ->and($recursable->hash)->toBe(hash('xxh128', $signature));
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

test('resolves callback on resolve', function () {
    $recursable = new RecursableStub(fn () => 'foo');

    expect($recursable->started())->toBeFalse()
        ->and($recursable->running())->toBeFalse()
        ->and($recursable->recursing())->toBeFalse()
        ->and($recursable->finished())->toBeFalse()
        ->and($recursable->expose_started)->toBeFalse()
        ->and($recursable->expose_stackDepth)->toBe(0)
        ->and($recursable->resolve())->toBe('foo')
        ->and($recursable->started())->toBeTrue()
        ->and($recursable->running())->toBeFalse()
        ->and($recursable->recursing())->toBeFalse()
        ->and($recursable->finished())->toBeTrue()
        ->and($recursable->expose_started)->toBeTrue()
        ->and($recursable->expose_stackDepth)->toBe(0)
        ->and(fn () => $recursable->resolve())->toThrow(RecursionException::class);
});

test('resolves callback on invoke', function () {
    $recursable = new RecursableStub(fn () => 'foo');

    expect($recursable->started())->toBeFalse()
        ->and($recursable->running())->toBeFalse()
        ->and($recursable->recursing())->toBeFalse()
        ->and($recursable->finished())->toBeFalse()
        ->and($recursable->expose_started)->toBeFalse()
        ->and($recursable->expose_stackDepth)->toBe(0)
        ->and(call_user_func($recursable))->toBe('foo')
        ->and($recursable->started())->toBeTrue()
        ->and($recursable->running())->toBeFalse()
        ->and($recursable->recursing())->toBeFalse()
        ->and($recursable->finished())->toBeTrue()
        ->and($recursable->expose_started)->toBeTrue()
        ->and($recursable->expose_stackDepth)->toBe(0)
        ->and(fn () => call_user_func($recursable))->toThrow(RecursionException::class);
});

test('resolves on recursion value if recursively calling resolve', function () {
    $recursable = new RecursableStub(function () use (&$recursable) {
        expect($recursable->started())->toBeTrue()
            ->and($recursable->running())->toBeTrue()
            ->and($recursable->recursing())->toBeFalse()
            ->and($recursable->finished())->toBeFalse()
            ->and($recursable->expose_started)->toBeTrue()
            ->and($recursable->expose_stackDepth)->toBe(1);

        return $recursable->resolve();
    }, 'bar');

    expect($recursable->started())->toBeFalse()
        ->and($recursable->running())->toBeFalse()
        ->and($recursable->recursing())->toBeFalse()
        ->and($recursable->finished())->toBeFalse()
        ->and($recursable->expose_started)->toBeFalse()
        ->and($recursable->expose_stackDepth)->toBe(0)
        ->and($recursable->resolve())->toBe('bar')
        ->and($recursable->started())->toBeTrue()
        ->and($recursable->running())->toBeFalse()
        ->and($recursable->recursing())->toBeFalse()
        ->and($recursable->finished())->toBeTrue()
        ->and($recursable->expose_started)->toBeTrue()
        ->and($recursable->expose_stackDepth)->toBe(0)
        ->and(fn () => $recursable->resolve())->toThrow(RecursionException::class);
});

test('resolves on recursion value if recursively invoking', function () {
    $recursable = new RecursableStub(function () use (&$recursable) {
        expect($recursable->started())->toBeTrue()
            ->and($recursable->running())->toBeTrue()
            ->and($recursable->recursing())->toBeFalse()
            ->and($recursable->finished())->toBeFalse()
            ->and($recursable->expose_started)->toBeTrue()
            ->and($recursable->expose_stackDepth)->toBe(1);

        return call_user_func($recursable);
    }, 'bar');

    expect($recursable->started())->toBeFalse()
        ->and($recursable->running())->toBeFalse()
        ->and($recursable->recursing())->toBeFalse()
        ->and($recursable->finished())->toBeFalse()
        ->and($recursable->expose_started)->toBeFalse()
        ->and($recursable->expose_stackDepth)->toBe(0)
        ->and($recursable->resolve())->toBe('bar')
        ->and($recursable->started())->toBeTrue()
        ->and($recursable->running())->toBeFalse()
        ->and($recursable->recursing())->toBeFalse()
        ->and($recursable->finished())->toBeTrue()
        ->and($recursable->expose_started)->toBeTrue()
        ->and($recursable->expose_stackDepth)->toBe(0)
        ->and(fn () => $recursable->resolve())->toThrow(RecursionException::class);
});

test('if calls on recursion callable if recursively calling resolve', function () {
    $recursable = new RecursableStub(
        function () use (&$recursable) {
            expect($recursable->started())->toBeTrue()
                ->and($recursable->running())->toBeTrue()
                ->and($recursable->recursing())->toBeFalse()
                ->and($recursable->finished())->toBeFalse()
                ->and($recursable->expose_started)->toBeTrue()
                ->and($recursable->expose_stackDepth)->toBe(1);

            return $recursable->resolve();
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
        ->and($recursable->resolve())->toBe('baz')
        ->and($recursable->started())->toBeTrue()
        ->and($recursable->running())->toBeFalse()
        ->and($recursable->recursing())->toBeFalse()
        ->and($recursable->finished())->toBeTrue()
        ->and($recursable->expose_started)->toBeTrue()
        ->and($recursable->expose_stackDepth)->toBe(0)
        ->and(fn () => $recursable->resolve())->toThrow(RecursionException::class);
});

test('if calls on recursion callable if recursively invoking', function () {
    $recursable = new RecursableStub(
        function () use (&$recursable) {
            expect($recursable->started())->toBeTrue()
                ->and($recursable->running())->toBeTrue()
                ->and($recursable->recursing())->toBeFalse()
                ->and($recursable->finished())->toBeFalse()
                ->and($recursable->expose_started)->toBeTrue()
                ->and($recursable->expose_stackDepth)->toBe(1);

            return call_user_func($recursable);
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
        ->and($recursable->resolve())->toBe('baz')
        ->and($recursable->started())->toBeTrue()
        ->and($recursable->running())->toBeFalse()
        ->and($recursable->recursing())->toBeFalse()
        ->and($recursable->finished())->toBeTrue()
        ->and($recursable->expose_started)->toBeTrue()
        ->and($recursable->expose_stackDepth)->toBe(0)
        ->and(fn () => $recursable->resolve())->toThrow(RecursionException::class);
});

test('if sets on recursion to callback result if recursively resolving', function () {
    $recursable = new RecursableStub(
        function () use (&$recursable) {
            expect($recursable->started())->toBeTrue()
                ->and($recursable->running())->toBeTrue()
                ->and($recursable->recursing())->toBeFalse()
                ->and($recursable->finished())->toBeFalse()
                ->and($recursable->expose_started)->toBeTrue()
                ->and($recursable->expose_stackDepth)->toBe(1);

            $result = $recursable->resolve();

            expect($result)->toBe('baz')
                ->and($recursable->expose_recurseWith)->toBe('baz')
                ->and($recursable->expose_stackDepth)->toBe(1)
                ->and($recursable->recursing())->toBeFalse()
                ->and($recursable->resolve())->toBe($result);

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
        ->and($recursable->resolve())->toBe('baz')
        ->and($recursable->started())->toBeTrue()
        ->and($recursable->running())->toBeFalse()
        ->and($recursable->recursing())->toBeFalse()
        ->and($recursable->finished())->toBeTrue()
        ->and($recursable->expose_started)->toBeTrue()
        ->and($recursable->expose_stackDepth)->toBe(0)
        ->and(fn () => $recursable->resolve())->toThrow(RecursionException::class);
});

test('if sets on recursion to callback result if recursively invoking', function () {
    $recursable = new RecursableStub(
        function () use (&$recursable) {
            expect($recursable->started())->toBeTrue()
                ->and($recursable->running())->toBeTrue()
                ->and($recursable->recursing())->toBeFalse()
                ->and($recursable->finished())->toBeFalse()
                ->and($recursable->expose_started)->toBeTrue()
                ->and($recursable->expose_stackDepth)->toBe(1);

            $result = call_user_func($recursable);

            expect($result)->toBe('baz')
                ->and($recursable->expose_recurseWith)->toBe('baz')
                ->and($recursable->expose_stackDepth)->toBe(1)
                ->and($recursable->recursing())->toBeFalse()
                ->and(call_user_func($recursable))->toBe($result);

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
        ->and($recursable->resolve())->toBe('baz')
        ->and($recursable->started())->toBeTrue()
        ->and($recursable->running())->toBeFalse()
        ->and($recursable->recursing())->toBeFalse()
        ->and($recursable->finished())->toBeTrue()
        ->and($recursable->expose_started)->toBeTrue()
        ->and($recursable->expose_stackDepth)->toBe(0)
        ->and(fn () => $recursable->resolve())->toThrow(RecursionException::class);
});
