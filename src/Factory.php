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
    /** @var class-string<Recursable<mixed>>  */
    protected string $recursableClass;
    /** @var class-string<RecursionContext>  */
    protected string $contextClass;
    /** @var class-string<Trace>  */
    protected string $traceClass;
    /** @var class-string<Frame>  */
    protected string $frameClass;

    /**
     * @param ?class-string<Recursable<mixed>> $recursableClass
     * @param ?class-string<RecursionContext> $contextClass
     * @param ?class-string<Trace> $traceClass
     * @param ?class-string<Frame> $frameClass
     */
    final public function __construct(
        ?string $recursableClass = null,
        ?string $contextClass = null,
        ?string $traceClass = null,
        ?string $frameClass = null,
    ) {
        $this->recursableClass = $recursableClass ?: Recursable::class;
        $this->contextClass = $contextClass ?: RecursionContext::class;
        $this->traceClass = $traceClass ?: Trace::class;
        $this->frameClass = $frameClass ?: Frame::class;
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
            $signature ?: $context->signature,
        );

        return $recursable->forObject($object ?? $context->object);
    }

    /**
     * @param Trace|Frame[]|FrameArray[] $trace
     */
    public function makeContext(callable $callback, Trace|array $trace = []): RecursionContext
    {
        $trace = $this->makeTrace($trace);

        return $trace->empty()
            ? $this->makeContextFromCallable($callback)
            : $this->makeContextFromTrace($trace);
    }

    public function makeContextFromTrace(Trace $trace): RecursionContext
    {
        if ($trace->empty()) {
            throw InvalidContextException::make($trace);
        }

        $caller = $trace->frames()[0];
        $called = $trace->frames()[1] ?? null;

        $context = new $this->contextClass(...array_filter([
            'file' => $caller->file,
            'class' => $called?->class,
            'function' => $called?->function,
            'line' => $caller->line,
            'object' => $called?->object,
        ]));

        return $context;
    }

    public function makeContextFromCallable(callable $callable): RecursionContext
    {
        if (is_string($callable) || $callable instanceof Closure) {
            return $this->makeContextFromFunction($callable);
        } elseif (is_array($callable)) {
            /** @var CallableArray $callable */
            return $this->makeContextFromCallableArray($callable);
        } else {
            /** @var callable&object $callable */
            return $this->makeContextFromObject($callable);
        }
    }

    public function makeContextFromFunction(Closure|string $callable): RecursionContext
    {
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

        $class = new ReflectionClass($callable);
        $method = $class->getMethod('__invoke');

        return new $this->contextClass(
            $class->getFileName() ?: '',
            $class->getName(),
            $method->getName(),
            $method->getStartLine() ?: 0,
            $callable,
        );
    }

    /**
     * @param array{0: object|class-string, 1: non-empty-string} $callable
     */
    public function makeContextFromCallableArray(array $callable): RecursionContext
    {
        if (!is_callable($callable)) {
            throw InvalidContextException::make($callable);
        }

        $class = new ReflectionClass($callable[0]);

        $method = $class->getMethod($callable[1]);

        return new $this->contextClass(
            $class->getFileName() ?: '',
            $class->getName(),
            $method->getName(),
            $method->getStartLine() ?: 0,
            is_object($callable[0]) ? $callable[0] : null,
        );
    }

    /**
     * @param Trace|Frame[]|FrameArray[] $trace
     */
    public function makeTrace(array|Trace $trace): Trace
    {
        return $this->traceClass::make($trace);
    }

    /**
     * @param array{'file'?: string,'class'?: string,'function'?: string,'line'?: int,'object'?: ?object}|Frame $frame
     * @return Frame
     */
    public function makeFrame(array|Frame $frame): Frame
    {
        return $this->frameClass::make($frame);
    }
}
