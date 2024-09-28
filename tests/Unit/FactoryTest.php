<?php

declare(strict_types=1);

use RecursionGuard\Data\Frame;
use RecursionGuard\Data\RecursionContext;
use RecursionGuard\Data\Trace;
use RecursionGuard\Exception\InvalidContextException;
use RecursionGuard\Factory;
use Tests\Support\Stubs\FactoryStub;

covers(Factory::class);

it('makes frame', function ($from) {
    $factory = new Factory();

    $frame = $factory->makeFrame($from);

    expect($frame->file)->toBe($from['file'] ?? '')
        ->and($frame->function)->toBe($from['function'] ?? null)
        ->and($frame->class)->toBe($from['class'] ?? null)
        ->and($frame->line)->toBe($from['line'] ?? 0)
        ->and($frame->object)->toBe($from['object'] ?? null)
        ->and($frame->jsonSerialize())->toBe([
            'file' => $from['file'] ?? '',
            'function' => $from['function'] ?? null,
            'class' => $from['class'] ?? null,
            'line' => $from['line'] ?? 0,
            'object' => $from['object'] ?? null,
        ]);
})->with([
    'none' => [[]],
    'file' => [['file' => 'foo.php']],
    'line' => [['line' => 42]],
    'function' => [['function' => 'foo']],
    'class' => [['class' => 'foo']],
    'object' => [['object' => (object) []]],
    'all' => [['file' => 'foo.php', 'line' => 42, 'function' => 'foo', 'class' => 'foo', 'object' => (object) []]],
    'existing frame' => [new Frame('foo.php', 'baz', 'bar', 42, (object) [])],
    'unneccessary keys' => [['type' => '->', 'args' => []]],
]);

it('clones frame when making frame from existing frame', function () {
    $factory = new Factory();
    $existing = new Frame('foo.php', 'baz', 'bar', 42, (object) []);

    $frame = $factory->makeFrame($existing);

    expect($frame)->toEqual($existing)
        ->not->toBe($existing);
});

it('makes trace from array', function ($from, $expected, $withoutEmpty) {
    $factory = new Factory();

    $trace = $factory->makeTrace($from);

    expect($trace->count())->toEqual(count($expected))
        ->and($trace->frames)->toEqual($expected)
        ->and($trace->frames())->toEqual($withoutEmpty)
        ->and($trace->frames(true))->toEqual($expected)
        ->and($trace->empty())->toEqual(empty($withoutEmpty))
        ->and($trace->jsonSerialize())->toEqual($expected);
})->with('traces');

it('makes trace from trace', function ($from, $expected, $withoutEmpty) {
    $factory = new Factory();

    $existing = $factory->makeTrace($from);

    $trace = $factory->makeTrace($existing);

    expect($trace)->not->toBe($existing)
        ->and($trace->count())->toEqual(count($expected))
        ->and($trace->frames)->toEqual($expected)
        ->and($trace->frames())->toEqual($withoutEmpty)
        ->and($trace->frames(true))->toEqual($expected)
        ->and($trace->empty())->toEqual(empty($withoutEmpty))
        ->and($trace->jsonSerialize())->toEqual($expected);
    ;
})->with('traces');

it('should make context from valid trace', function ($from, $expected) {
    $factory = new Factory();

    $trace = $factory->makeTrace($from);

    $context = $factory->makeContextfromTrace($trace);

    expect($context->file)->toBe($expected['file'])
        ->and($context->line)->toBe($expected['line'])
        ->and($context->function)->toBe($expected['function'])
        ->and($context->class)->toBe($expected['class'])
        ->and($context->object)->toEqual($expected['object'])
        ->and($context->signature())->toBe($expected['signature'])
        ->and($context->jsonSerialize())->toEqual($expected);
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
    'only file' => [
        [['file' => 'foo.php']],
        [
            'file' => 'foo.php',
            'line' => 0,
            'function' => '',
            'class' => '',
            'object' => null,
            'signature' => 'foo.php:0',
        ],
    ],
    'only line' => [
        [['line' => 42]],
        [
            'file' => '',
            'line' => 42,
            'function' => '',
            'class' => '',
            'object' => null,
            'signature' => ':42',
        ],
    ],
    'file and function' => [
        [['file' => 'foo.php'], ['function' => 'bar']],
        [
            'file' => 'foo.php',
            'line' => 0,
            'function' => 'bar',
            'class' => '',
            'object' => null,
            'signature' => 'foo.php:bar',
        ],
    ],
    'line and function' => [
        [['line' => 42], ['function' => 'foo']],
        [
            'file' => '',
            'line' => 42,
            'function' => 'foo',
            'class' => '',
            'object' => null,
            'signature' => ':foo',
        ],
    ],
    'file and class' => [
        [['file' => 'foo.php'], ['class' => 'bar']],
        [
            'file' => 'foo.php',
            'line' => 0,
            'function' => '',
            'class' => 'bar',
            'object' => null,
            'signature' => 'foo.php:bar@0',
        ],
    ],
    'line and class' => [
        [['line' => 42], ['class' => 'foo']],
        [
            'file' => '',
            'line' => 42,
            'function' => '',
            'class' => 'foo',
            'object' => null,
            'signature' => ':foo@42',
        ],
    ],
    'file and object' => [
        [['file' => 'foo.php'], ['object' => (object) []]],
        [
            'file' => 'foo.php',
            'line' => 0,
            'function' => '',
            'class' => '',
            'object' => (object) [],
            'signature' => 'foo.php:0',
        ],
    ],
]);

it('should throw an exception when trying to make context from invalid trace', function ($from) {
    $factory = new Factory();

    $trace = $factory->makeTrace($from);

    $factory->makeContextfromTrace($trace);
})->throws(InvalidContextException::class)->with([
    'empty array' => [[]],
    'array of empty arrays' => [[[], [], []]],
    'invalid backtrace' => [[['foo' => 'bar'], ['foo' => 'bar'], ['foo' => 'bar']]],
]);

it('should make context from functions', function (callable $from) {
    $factory = new Factory();

    $context = $factory->makeContextFromFunction($from);

    $reflector = new ReflectionFunction($from);

    $name = $reflector->getName() === '{closure}' ? (string)$reflector : $reflector->getName();

    expect($context->file)->toBe($reflector->getFileName() ?: '')
        ->and($context->function)->toBe($name)
        ->and($context->class)->toBe($reflector->getClosureScopeClass()?->getName() ?: '')
        ->and($context->line)->toBe($reflector->getStartLine() ?: 0)
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

it('should throw an exception when trying to make context from invalid function', function ($from) {
    $factory = new Factory();

    $factory->makeContextFromFunction($from);
})->throws(InvalidContextException::class)->with([
    'function that does not exist' => ['fooBarBaz'],
    'language construct' => ['isset'],
    'string with spaces' => ['foo bar'],
    'empty string' => [''],
]);

it('should make context from callable array', function (array $from) {
    $factory = new Factory();

    $context = $factory->makeContextFromCallableArray($from);

    $class = new ReflectionClass($from[0]);
    $method = $class->getMethod($from[1]);

    expect($context->file)->toBe($class->getFileName() ?: '')
        ->and($context->function)->toBe($method->getName())
        ->and($context->class)->toBe($class->getName())
        ->and($context->line)->toBe($method->getStartLine() ?: 0)
        ->and($context->object)->toEqual(is_object($from[0]) ? $from[0] : null)
        ->and($context->signature())->toBe(sprintf(
            '%s:%s@%s',
            $class->getFileName(),
            $class->getName(),
            $method->getName(),
        ));
})->with([
    'class string' => [[DateTime::class, 'createFromFormat']],
    'object method' => [[new \DateTime(), 'format']],
    'object static method' => [[new \DateTime(), 'createFromFormat']],
    'invokeable class' => [[new class () {
        public function __invoke(): void
        {
            //
        }
    }, '__invoke']],
]);

it('should throw an exception when trying to make context from invalid array', function ($from) {
    $factory = new Factory();

    $factory->makeContextFromCallableArray($from);
})->throws(InvalidContextException::class)->with([
    'empty array' => [[]],
    'single element array' => [[DateTime::class]],
    'invalid function' => [[DateTime::class, 'fooBarBaz']],
    'empty strings' => [['', '']],
    'too many elements' => [[DateTime::class, 'createFromFormat', 'foo']],
    'string keys' => [['class' => DateTime::class, 'method' => 'createFromFormat']],
    'integer elements' => [[DateTime::class, 42]],
    'reversed elements' => [['createFromFormat', DateTime::class]],
]);

it('should make context from invokable object', function () {
    $from = new class () {
        public function __invoke(): void
        {
            //
        }
    };

    $factory = new Factory();

    $context = $factory->makeContextFromObject($from);

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

it('should throw an exception when trying to make context from non-invokable object', function () {
    $factory = new Factory();

    $object = new class () {
        //
    };

    $factory->makeContextFromObject($object);
})->throws(InvalidContextException::class);

it('uses the correct context make method', function ($closure, $trace, $expected, $method) {
    $observer = $this->createMock(Factory::class);
    $factory = new FactoryStub();
    $factory->attach($observer);

    if ($expected === 'trace') {
        $frames = array_map(function ($frame) {
            $parts = array_filter(array_intersect_key(
                $frame,
                array_flip(['file', 'class', 'function', 'line', 'object']),
            ));

            return new Frame(...$parts);
        }, $trace);

        $made = new Trace($frames);

        $observer->expects($this->once())
            ->method('makeTrace')
            ->with($trace);

        if ($made->empty()) {
            $this->expectException(InvalidContextException::class);
        }

        $observer->expects($this->once())
            ->method($method)
            ->with($this->equalTo($made));
    } else {
        $observer->expects($this->once())
            ->method($method)
            ->with($closure);
    }

    $factory->makeContext($closure, $trace);
})->with([
    'array of empty arrays' => [fn () => 'foo', [[], []], 'trace', 'makeContextFromTrace'],
    'single array' => [fn () => 'foo', [['file' => '']], 'trace', 'makeContextFromTrace'],
    'backtrace array' => [
        fn () => 'foo',
        [['file' => 'foo.php', 'line' => 1, 'function' => 'bar', 'class' => 'baz', 'object' => (object) []]],
        'trace',
        'makeContextFromTrace'
    ],
    'short closure' => [fn () => 'foo', [], 'closure', 'makeContextFromCallable'],
    'long closure' => [
        function () {
            return 'foo';
        },
        [],
        'closure',
        'makeContextFromCallable'
    ],
    'callable string' => ['rand', [], 'closure', 'makeContextFromCallable'],
    'first class callable' => [rand(...), [], 'closure', 'makeContextFromCallable'],
    'static callable array' => [[DateTime::class, 'createFromFormat'], [], 'closure', 'makeContextFromCallable'],
    'instance callable array' => [[new DateTime(), 'format'], [], 'closure', 'makeContextFromCallable'],
    'invokable class' => [new class () {
        public function __invoke(): void
        {
            //
        }
    }, [], 'closure', 'makeContextFromCallable'],
]);

it('uses the correct context callable make method', function ($from, $method) {
    $observer = $this->createMock(Factory::class);

    $observer->expects($this->once())
        ->method($method)
        ->with($from);

    $factory = new FactoryStub();

    $factory->attach($observer);

    $factory->makeContextFromCallable($from);
})->with([
    'short closure' => [fn () => 'foo', 'makeContextFromFunction'],
    'long closure' => [
        function () {
            return 'foo';
        },
        'makeContextFromFunction'
    ],
    'callable string' => ['rand', 'makeContextFromFunction'],
    'first class callable' => [rand(...), 'makeContextFromFunction'],
    'static callable array' => [[DateTime::class, 'createFromFormat'], 'makeContextFromCallableArray'],
    'instance callable array' => [[new DateTime(), 'format'], 'makeContextFromCallableArray'],
    'invokable class' => [new class () {
        public function __invoke(): void
        {
            //
        }
    }, 'makeContextFromObject'],
]);

it('makes a recursable signature from a backtrace', function ($trace, $signature) {
    $observer = $this->createMock(Factory::class);
    $factory = new FactoryStub();
    $factory->attach($observer);

    $callback = fn () => null;

    $observer->expects($this->once())
        ->method('makeContext')
        ->with($callback, $trace);

    $recursable = $factory->makeRecursable($callback, backTrace: $trace);

    expect($recursable->signature)->toBe($signature)
        ->and($recursable->hash)->toBe(hash('xxh128', $signature));
})->with([
    'single frame trace' => [
        [['file' => 'foo.php', 'line' => 42]],
        'foo.php:42',
    ],
    'multi frame trace' => [
        [
            ['file' => 'foo.php', 'line' => 42],
            ['class' => 'bar', 'function' => 'baz', 'object' => null],
        ],
        'foo.php:bar@baz',
    ],
]);

it('overrides parts when making recursable', function () {
    $factory = new Factory();

    $callable = new class () {
        public function __invoke(): string
        {
            return 'foo';
        }
    };
    $object = (object) [];

    $one = $factory->makeRecursable($callable);
    $two = $factory->makeRecursable($callable, object: $object);
    $three = $factory->makeRecursable($callable, signature: 'foo');
    $four = $factory->makeRecursable($callable, backTrace: [
        ['file' => 'foo.php', 'line' => 42],
        ['class' => 'bar', 'function' => 'baz', 'object' => $object],
    ]);

    $class = new ReflectionClass($callable);
    $method = $class->getMethod('__invoke');

    $file = $class->getFileName() ?: '';
    $function = $method->getName();
    $class = $class->getName();
    $line = $method->getStartLine() ?: 0;
    $signature = sprintf('%s:%s', $file, ($class ? ($class . '@') : '') . ($function ?: $line));

    expect($one->signature)->toBe($signature)
        ->and($one->hash)->toBe(hash('xxh128', $signature))
        ->and($one->object())->toBe($callable)
        ->and($two->signature)->toBe($signature)
        ->and($two->hash)->toBe(hash('xxh128', $signature))
        ->and($two->object())->toBe($object)
        ->and($three->signature)->toBe('foo')
        ->and($three->hash)->toBe(hash('xxh128', 'foo'))
        ->and($three->object())->toBe($callable)
        ->and($four->signature)->toBe('foo.php:bar@baz')
        ->and($four->hash)->toBe(hash('xxh128', 'foo.php:bar@baz'))
        ->and($four->object())->toBe($object);
});
