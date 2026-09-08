<?php

declare(strict_types=1);

namespace RecursionGuard\Data;

use ArrayAccess;
use JsonSerializable;
use RecursionGuard\Helper\Obj;

/**
 * @template TArrayKey of array-key
 * @template TValue of mixed
 *
 * @implements ArrayAccess<TArrayKey, TValue>
 */
abstract readonly class BaseData implements ArrayAccess, JsonSerializable
{
    public function empty(): bool
    {
        $vars = get_object_vars($this);

        foreach (Obj::defaults($this) as $property => $default) {
            if (
                match (true) {
                    is_object($default)
                        => !is_object($vars[$property])
                            || !$vars[$property] instanceof $default
                            || $default != $vars[$property],
                    is_array($default) => $default != $vars[$property],
                    default => $default !== $vars[$property],
                }
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array-key $offset
     * @return ($offset is string ? bool : false)
     */
    public function offsetExists(mixed $offset): bool
    {
        return is_string($offset) && property_exists($this, $offset);
    }

    /**
     * @param int|string $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->offsetExists($offset) ? $this->$offset : null;
    }

    /**
     * @param int|string $offset
     */
    final public function offsetSet(mixed $offset, mixed $value): never
    {
        throw new \RuntimeException(static::class . ' is read-only');
    }

    /**
     * @param int|string $offset
     */
    final public function offsetUnset(mixed $offset): never
    {
        throw new \RuntimeException(static::class . ' is read-only');
    }

    /**
     * @return array<array-key, TValue>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
