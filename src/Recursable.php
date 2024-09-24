<?php

declare(strict_types=1);

namespace RecursionGuard;

use Closure;
use RecursionGuard\Data\Frame;
use RecursionGuard\Data\RecursionContext;
use RecursionGuard\Data\Trace;
use RecursionGuard\Exception\RecursionException;

/**
 * @template TReturnType
 * @phpstan-import-type FrameArray from Frame
 * @phpstan-import-type TraceArray from Trace
 */
class Recursable
{
    public readonly string $signature;
    public readonly string $hash;
    public readonly Closure $callback;
    protected mixed $recurseWith;
    protected object|null $object;
    protected bool $started = false;
    protected int $stackDepth = 0;

    /**
     * @param callable(): TReturnType $callback
     * @param TReturnType|callable(): TReturnType $recurseWith
     */
    final public function __construct(
        callable $callback,
        mixed $recurseWith = null,
        string $signature = '',
    ) {
        $this->callback = $callback instanceof Closure ? $callback : $callback(...);
        $this->signature = $signature ?: RecursionContext::fromCallable($callback)->signature();
        $this->hash = static::hashSignature($this->signature);
        $this->recurseWith = $recurseWith;
    }

    /**
     * @param callable(): TReturnType $callback
     * @param TReturnType|callable(): TReturnType $onRecursion
     * @param object|null $object
     * @param string|null $signature
     * @param array<int, FrameArray> $backTrace
     *
     * @return static
     * @throws \ReflectionException
     */
    public static function make(
        callable $callback,
        mixed $onRecursion = null,
        ?object $object = null,
        ?string $signature = null,
        array $backTrace = [],
    ): static {
        $context = RecursionContext::make($backTrace ?: $callback);

        return (new static(
            $callback,
            $onRecursion,
            $signature ?: $context->signature(),
        ))->forObject($object ?? $context->object);
    }

    public function object(): ?object
    {
        return $this->object;
    }

    /**
     * @return $this
     */
    public function forObject(?object $object): static
    {
        $this->object ??= $object;

        return $this;
    }

    /**
     * Set the value to return when recursing.
     *
     * @param TReturnType|callable(): TReturnType $value
     * @return $this
     */
    public function andReturn(mixed $value): static
    {
        $this->recurseWith = $value;

        return $this;
    }

    public function started(): bool
    {
        return $this->started;
    }

    public function running(): bool
    {
        return $this->started() && $this->stackDepth > 0;
    }

    public function finished(): bool
    {
        return $this->started() && !$this->running();
    }

    public function recursing(): bool
    {
        return $this->running() && $this->stackDepth > 1;
    }

    /**
     * @return TReturnType|callable(): TReturnType
     */
    public function resolve(): mixed
    {
        if ($this->finished() || $this->recursing()) {
            throw RecursionException::make($this);
        }

        try {
            $this->stackDepth++;

            if (!$this->started()) {
                $this->started = true;

                return call_user_func($this->callback);
            }

            if (is_callable($this->recurseWith)) {
                $this->andReturn(call_user_func($this->recurseWith));
            }

            return $this->recurseWith;
        } finally {
            $this->stackDepth--;
        }
    }

    /**
     * @return TReturnType|callable(): TReturnType
     */
    public function __invoke(): mixed
    {
        return $this->resolve();
    }

    /**
     * Computes the hash of the recursable from the given signature.
     *
     * @param string $signature
     * @return string
     */
    protected static function hashSignature(string $signature): string
    {
        return hash('xxh128', $signature);
    }
}
