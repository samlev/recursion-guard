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
 *
 * @method string signature()
 * @method string hash()
 * @method Closure callback()
 * @method mixed recurseWith()
 * @method bool started()
 * @method int stackDepth()
 * @method object|null object()
 */
class Recursable
{
    protected readonly string $signature;
    protected readonly string $hash;
    protected readonly Closure $callback;
    protected mixed $recurseWith = null;
    protected object|null $object = null;
    protected bool $started;
    protected int $stackDepth;

    /**
     * @param callable(): TReturnType $callback
     * @param TReturnType|callable(): TReturnType $recurseWith
     * @throws \ReflectionException
     */
    final public function __construct(
        callable $callback,
        mixed $recurseWith = null,
        string $signature = '',
    ) {
        $this->callback = $callback(...);
        $this->signature = $signature ?: Recurser::instance()->factory->makeContextFromCallable($callback)->signature;
        $this->hash = hash('xxh128', $this->signature);
        $this->recurseWith = $recurseWith;
        $this->started = false;
        $this->stackDepth = 0;
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

    public function overflown(): bool
    {
        return $this->stackDepth < 0;
    }

    public function running(): bool
    {
        return $this->started() && $this->stackDepth > 0;
    }

    public function finished(): bool
    {
        return $this->started() && !$this->running() && !$this->overflown();
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
        if ($this->finished() || $this->recursing() || $this->overflown()) {
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
     * @param string $method
     * @param array<array-key, never> $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters): mixed
    {
        if (property_exists($this, $method)) {
            return $this->$method;
        }

        throw new \BadMethodCallException(sprintf('Call to undefined method %s::%s()', static::class, $method));
    }
}
