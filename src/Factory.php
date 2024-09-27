<?php

declare(strict_types=1);

namespace RecursionGuard;

use Closure;
use RecursionGuard\Data\Frame;
use RecursionGuard\Data\RecursionContext;
use RecursionGuard\Data\Trace;
use RecursionGuard\Exception\InvalidContextException;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;

/**
 * @phpstan-import-type FrameArray from Frame
 * @phpstan-type CallableArray callable&array{0: object|class-string, 1: non-empty-string}
 */
class Factory
{
    /**
     * @param class-string<Recursable<mixed>> $recursableClass
     * @param class-string<RecursionContext> $contextClass
     * @param class-string<Trace> $traceClass
     * @param class-string<Frame> $frameClass
     */
    public function __construct(
        protected string $recursableClass = Recursable::class,
        protected string $contextClass = RecursionContext::class,
        protected string $traceClass = Trace::class,
        protected string $frameClass = Frame::class,
    ) {
        //
    }

    /**
     * @template TReturnType
     *
     * @param callable(): TReturnType $callback
     * @param TReturnType|callable(): TReturnType $onRecursion
     * @param Frame[]|FrameArray[] $backTrace
     * @return Recursable<TReturnType>
     */
    public function makeRecursable(
        callable $callback,
        mixed $onRecursion = null,
        ?object $object = null,
        ?string $signature = null,
        Trace|array $backTrace = [],
    ): Recursable {
        $context = $this->makeContext($callback, $backTrace);

        /** @var Recursable<TReturnType> $recursable */
        $recursable = new $this->recursableClass(
            $callback,
            $onRecursion,
            $signature ?: $context->signature()
        );

        return $recursable->forObject($object ?? $context->object);
    }

    /**
     * @param Trace|Frame[]|FrameArray[] $backTrace
     */
    public function makeContext(callable $callback, Trace|array $backTrace = []): RecursionContext
    {
        return $backTrace
            ? $this->makeContextFromTrace($this->makeTrace($backTrace))
            : $this->makeContextFromCallable($callback);
    }

    public function makeContextFromTrace(Trace $trace): RecursionContext
    {
        if ($trace->empty()) {
            throw InvalidContextException::make($trace);
        }

        $caller = $trace->frames()[0];
        $called = $trace->frames()[1] ?? null;

        return new $this->contextClass(...array_filter([
            'file' => $caller->file,
            'class' => $called?->class,
            'function' => $called?->function,
            'line' => $caller->line,
            'object' => $called?->object,
        ]));
    }

    public function makeContextFromCallable(callable $callable): RecursionContext
    {
        if (is_string($callable) || $callable instanceof Closure) {
            return $this->makeContextFromFunction($callable);
        } elseif (is_array($callable)) {
            /** @var CallableArray $callable */
            return $this->makeContextFromCallableArray($callable);
        } elseif (is_object($callable)) {
            /** @var callable&object $callable */
            return $this->makeContextFromObject($callable);
        }

        throw InvalidContextException::make($callable);
    }

    public function makeContextFromFunction(Closure|string $callable): RecursionContext
    {
        if (!is_callable($callable)) {
            throw InvalidContextException::make($callable);
        }

        try {
            $reflector = new ReflectionFunction($callable);

            return new $this->contextClass(
                $reflector->getFileName() ?: '',
                $reflector->getClosureScopeClass()?->getName() ?? '',
                $reflector->getName() === '{closure}'
                    ? (string)$reflector
                    : $reflector->getName(),
                $reflector->getStartLine() ?: 0,
                $reflector->getClosureThis(),
            );
        } catch (ReflectionException $e) {
            throw InvalidContextException::make($callable, previous: $e);
        }
    }

    public function makeContextFromObject(object $callable): RecursionContext
    {
        if (!is_callable($callable)) {
            throw InvalidContextException::make($callable);
        }

        try {
            $class = new ReflectionClass($callable);
            $method = $class->getMethod('__invoke');

            return new $this->contextClass(
                $class->getFileName() ?: '',
                $class->getName(),
                $method->getName(),
                $method->getStartLine() ?: 0,
                $callable,
            );
        } catch (ReflectionException $e) {
            throw InvalidContextException::make($callable, previous: $e);
        }
    }

    /**
     * @param array{0: object|class-string, 1: non-empty-string} $callable
     */
    public function makeContextFromCallableArray(array $callable): RecursionContext
    {
        if (!is_callable($callable)) {
            throw InvalidContextException::make($callable);
        }

        try {
            $class = new ReflectionClass($callable[0]);

            $method = $class->getMethod($callable[1]);

            return new $this->contextClass(
                $class->getFileName() ?: '',
                $class->getName(),
                $method->getName(),
                $method->getStartLine() ?: 0,
                is_object($callable[0]) ? $callable[0] : null,
            );
        } catch (ReflectionException $e) {
            throw InvalidContextException::make($callable, previous: $e);
        }
    }

    /**
     * @param Trace|Frame[]|FrameArray[] $trace
     */
    public function makeTrace(array|Trace $trace): Trace
    {
        return new $this->traceClass(
            array_map($this->makeFrame(...), $trace instanceof Trace ? $trace->frames(true) : $trace)
        );
    }

    /**
     * @param array<string, mixed>|Frame $frame
     */
    public function makeFrame(array|Frame $frame): Frame
    {
        if ($frame instanceof Frame) {
            return new $this->frameClass(...$frame->jsonSerialize());
        }

        return new $this->frameClass(
            ...array_intersect_key(
                $frame,
                array_flip(['file', 'class', 'function', 'line', 'object'])
            )
        );
    }
}
