<?php

declare(strict_types=1);

namespace RecursionGuard;

use WeakMap;

class Recurser
{
    /**
     * The current globally used instance.
     *
     * @var static|null
     */
    protected static ?self $instance = null;

    /**
     * An empty object to use as the scope for non-object-based uses.
     *
     * @var object
     */
    public readonly object $defaultScope;

    /**
     * Create a new once instance.
     *
     * @var \WeakMap<object, array<string, Recursable<mixed>>> $cache
     */
    protected readonly WeakMap $cache;

    /**
     * Create a new once instance.
     *
     * @return void
     */
    final public function __construct(
        public readonly Factory $factory = new Factory(),
    ) {
        $this->cache = new WeakMap();
        $this->defaultScope = (object)[];
    }

    /**
     * Get or create the current globally used instance.
     *
     * @return static
     */
    public static function instance(): static
    {
        return static::$instance ??= new static();
    }

    /**
     * Flush the recursion cache.
     *
     * @return void
     */
    public static function flush(): void
    {
        static::$instance = null;
    }

    /**
     * Call a callable, and prevent it from being called recursively within the same call stack.
     *
     * @template TReturnType of mixed
     *
     * @param callable(): TReturnType $callback
     * @param TReturnType|callable(): TReturnType $onRecursion
     * @param object|null $for
     * @param string|null $as
     * @return TReturnType
     */
    public static function call(
        callable $callback,
        mixed $onRecursion = null,
        ?object $for = null,
        ?string $as = null,
    ): mixed {
        $instance = static::instance();

        $recursable = $instance->factory->makeRecursable(
            $callback,
            $onRecursion,
            $for,
            $as,
            debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2),
        );

        return $instance->guard($recursable);
    }

    /**
     * Prevent a recursable from being called multiple times within the same call stack.
     *
     * @template TReturnType of mixed
     *
     * @param Recursable<TReturnType> $target
     * @return TReturnType
     */
    public function guard(Recursable $target): mixed
    {
        $target->forObject($this->defaultScope);

        if ($current = $this->find($target)) {
            return call_user_func($current);
        }

        try {
            return call_user_func($this->setValue($target));
        } finally {
            $this->release($target);
        }
    }

    /**
     * Release a recursable from the stack.
     *
     * @param Recursable<mixed> $target
     * @return void
     */
    public function release(Recursable $target): void
    {
        $stack = $this->getStack($target->object() ?? $this->defaultScope);
        unset($stack[$target->hash]);
        $this->setStack($target->object() ?? $this->defaultScope, $stack);
    }

    /**
     * Get the current copy of the recursable from the stack.
     *
     * @param Recursable<mixed> $target
     * @return Recursable<mixed>|null
     */
    public function find(Recursable $target): ?Recursable
    {
        return $this->getStack($target->object() ?? $this->defaultScope)[$target->hash] ?? null;
    }

    /**
     * Get the stack of methods being called recursively for the given object.
     *
     * @param object $instance
     * @return array<string, Recursable<mixed>>
     */
    protected function getStack(object $instance): array
    {
        return $this->cache->offsetExists($instance) ? $this->cache->offsetGet($instance) : [];
    }

    /**
     * Set the stack of methods being called recursively for the given object.
     *
     * @param object $instance
     * @param array<string, Recursable<mixed>> $stack
     */
    protected function setStack(object $instance, array $stack): void
    {
        if ($stack) {
            $this->cache->offsetSet($instance, $stack);
        } elseif ($this->cache->offsetExists($instance)) {
            $this->cache->offsetUnset($instance);
        }
    }

    /**
     * Set the currently stored value of the given recursable.
     *
     * @param Recursable<mixed> $target
     * @return Recursable<mixed>
     */
    protected function setValue(Recursable $target): Recursable
    {
        $stack = $this->getStack($target);
        $stack[$target->hash] = $target;
        $this->setStack($target->object() ?? $this->defaultScope, $stack);

        return $stack[$target->hash];
    }
}
