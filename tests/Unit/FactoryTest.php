<?php

declare(strict_types=1);

use Mockery as m;
use RecursionGuard\Data\Frame;
use RecursionGuard\Data\RecursionContext;
use RecursionGuard\Data\Trace;
use RecursionGuard\Exception\InvalidContextException;
use RecursionGuard\Factory;
use RecursionGuard\Recursable;
use Tests\Support\Stubs\FactoryStub;
use Tests\Support\Stubs\FrameStub;
use Tests\Support\Stubs\RecursableStub;
use Tests\Support\Stubs\RecursionContextStub;
use Tests\Support\Stubs\TraceStub;

covers(Factory::class);

it('makes frame with from configured frame class', function ($from) {
    $this->spy()->expect(FrameStub::class . '::make', [$from]);

    $default = new Factory();
    $one = $default->makeFrame($from);

    $factory = new Factory(frameClass: FrameStub::class);
    $two = $factory->makeFrame($from);

    expect($one)
        ->toBeInstanceOf(Frame::class)
        ->not->toBeInstanceOf(FrameStub::class)
        ->and($two)->toBeInstanceOf(Frame::class)
        ->and($two)->toBeInstanceOf(FrameStub::class)
        ->and($two->jsonSerialize())->toEqual($one->jsonSerialize());
})->with('frames');

it('makes trace from array with configured trace class', function ($from, $expected, $withoutEmpty) {
    $this->spy()->expect(TraceStub::class . '::make', [$from]);
    $default = new Factory();
    $one = $default->makeTrace($from);

    $factory = new Factory(traceClass: TraceStub::class);
    $two = $factory->makeTrace($from);

    expect($one)
        ->toBeInstanceOf(Trace::class)
        ->not->toBeInstanceOf(TraceStub::class)
        ->and($two)->toBeInstanceOf(Trace::class)
        ->and($two)->toBeInstanceOf(TraceStub::class)
        ->and($two->jsonSerialize())->toEqual($one->jsonSerialize());
})->with('traces');

it('makes context from trace with configured trace class', function () {
    $trace = new Trace([
        new Frame('foo.php', 'foo', 'foo', 42, (object) []),
        new Frame('bing.php', 'bang', 'boom', 99, new Frame()),
    ]);

    $default = new Factory();
    $one = $default->makeContextFromTrace($trace);

    $factory = new Factory(contextClass: RecursionContextStub::class);
    $two = $factory->makeContextFromTrace($trace);

    expect($one)
        ->toBeInstanceOf(RecursionContext::class)
        ->not->toBeInstanceOf(RecursionContextStub::class)
        ->and($two)->toBeInstanceOf(RecursionContext::class)
        ->and($two)->toBeInstanceOf(RecursionContextStub::class)
        ->and($two->jsonSerialize())->toEqual($one->jsonSerialize());
});

it('makes context from trace array if array is not empty', function ($from, $empty) {
    $factory = m::mock(Factory::class)->makePartial();

    $callable = fn () => null;
    $context = new RecursionContext(signature: $empty ? 'callable' : 'trace');
    $trace = Trace::make($from);

    $factory->shouldReceive('makeTrace')->once()->andReturn($trace);
    $factory->shouldReceive($empty ? 'makeContextFromTrace' : 'makeContextFromCallable')->never();
    $factory->shouldReceive($empty ? 'makeContextFromCallable' : 'makeContextFromTrace')
        ->once()
        ->withArgs(function ($with) use ($empty, $callable, $trace) {
            return $empty ? $with === $callable : $with === $trace;
        })
        ->andReturn($context);

    expect($factory->makeContext($callable, $from))->toBe($context);
})->with('frame arrays');

it('makes context from trace object if trace is not empty', function ($trace, $empty) {
    $factory = m::mock(Factory::class)->makePartial();

    $callable = fn () => null;
    $context = new RecursionContext(signature: $empty ? 'callable' : 'trace');

    $factory->shouldReceive('makeTrace')->once()->andReturn($trace);
    $factory->shouldReceive($empty ? 'makeContextFromTrace' : 'makeContextFromCallable')->never();
    $factory->shouldReceive($empty ? 'makeContextFromCallable' : 'makeContextFromTrace')
        ->once()
        ->withArgs(function ($with) use ($empty, $callable, $trace) {
            return $empty ? $with === $callable : $with === $trace;
        })
        ->andReturn($context);

    expect($factory->makeContext($callable, $trace))->toBe($context);
})->with('trace objects');

it('should make context from non-empty trace frames', function ($from, $empty) {
    $factory = new Factory();

    if ($empty) {
        expect(fn () => $factory->makeContextFromTrace($from))
            ->toThrow(InvalidContextException::class);
    } else {
        $context = $factory->makeContextFromTrace($from);

        $file = $from->frames()[0]->file;
        $class = $from->frames()[1]?->class ?? '';
        $function = $from->frames()[1]?->function ?? '';
        $line = $from->frames()[0]->line;
        $object = $from->frames()[1]->object ?? null;

        expect($context->file)->toBe($file)
            ->and($context->class)->toBe($class)
            ->and($context->function)->toBe($function)
            ->and($context->line)->toBe($line)
            ->and($context->object)->toEqual($object);
    }
})->with('trace objects');

it('should make context from the correct method with callable', function ($from, $type) {
    $factory = m::mock(Factory::class)->makePartial();

    $context = new RecursionContext(signature: $type);

    $methods = [
        'makeContextFromFunction' => true,
        'makeContextFromCallableArray' => true,
        'makeContextFromObject' => true,
    ];
    unset($methods[$type]);

    $factory->shouldReceive($type)
        ->once()
        ->withArgs(fn ($args) => $args === $from)
        ->andReturn($context);

    array_map(fn ($method) => $factory->shouldReceive($method)->never(), array_keys($methods));

    expect($factory->makeContextFromCallable($from))->toBe($context);
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
    'invokable class' => [
        new class () {
            public function __invoke(): void
            {
                //
            }
        },
        'makeContextFromObject'
    ],
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
        ->and($context->signature)->toBe(
            sprintf(
                '%s:%s',
                $reflector->getFileName(),
                ($reflector->getClosureScopeClass() ? $reflector->getClosureScopeClass()->getName() . '@' : '')
                . ($name ?: $reflector->getStartLine() ?: 0),
            )
        );
})->with([
    'short closure' => [fn () => 'foo'],
    'long closure' => [
        function () {
            return 'foo';
        }
    ],
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
        ->and($context->signature)->toBe(
            sprintf(
                '%s:%s@%s',
                $class->getFileName(),
                $class->getName(),
                $method->getName(),
            )
        );
})->with([
    'class string' => [[DateTime::class, 'createFromFormat']],
    'object method' => [[new \DateTime(), 'format']],
    'object static method' => [[new \DateTime(), 'createFromFormat']],
    'invokeable class' => [
        [
            new class () {
                public function __invoke(): void
                {
                    //
                }
            },
            '__invoke'
        ]
    ],
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
        ->and($context->signature)->toBe(
            sprintf(
                '%s:%s@%s',
                __FILE__,
                $class->getName(),
                '__invoke',
            )
        );
});

it('should throw an exception when trying to make context from non-invokable object', function () {
    $factory = new Factory();

    $object = new class () {
        //
    };

    $factory->makeContextFromObject($object);
})->throws(InvalidContextException::class);

it('makes a recursable from configured recursable class', function () {
    $default = new Factory();
    $factory = new Factory(RecursableStub::class);
    $callback = fn () => null;

    $one = $default->makeRecursable($callback);
    $two = $factory->makeRecursable($callback);

    expect($one)->toBeInstanceOf(Recursable::class)
        ->not->toBeInstanceOf(RecursableStub::class)
        ->and($two)->toBeInstanceOf(Recursable::class)
        ->toBeInstanceOf(RecursableStub::class)
        ->and($one->signature)->toBe($two->signature)
        ->and($one->hash)->toBe($two->hash);
});

it('overrides parts when making recursable', function () {
    $factory = new Factory();

    $callable = new class () {
        public function __invoke(): string
        {
            return 'foo';
        }
    };
    $object = (object)[];
    $otherObject = (object)[];

    $one = $factory->makeRecursable($callable);
    $two = $factory->makeRecursable($callable, object: $object);
    $three = $factory->makeRecursable($callable, signature: 'foo');
    $four = $factory->makeRecursable($callable, backTrace: [
        ['file' => 'foo.php', 'line' => 42],
        ['class' => 'bar', 'function' => 'baz', 'object' => $object],
    ]);
    $five = $factory->makeRecursable($callable, object: $otherObject, backTrace: [
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
        ->and($four->object())->toBe($object)
        ->and($five->object())->toBe($otherObject);
});
