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
                match (true) { // @pest-mutate-ignore: TrueToFalse
                    is_object($default)
                        => !is_object($vars[$property])
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
     * @param array-key $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->offsetExists($offset) ? $this->$offset : null;
    }

    /**
     * @param array-key $offset
     */
    final public function offsetSet(mixed $offset, mixed $value): never
    {
        if ($this->offsetExists($offset)) {
            throw new \Error('Cannot modify readonly property ' . $this->qualifyOffset($offset));
        }
        throw new \Error('Cannot create dynamic property ' . $this->qualifyOffset($offset));
    }

    /**
     * @param array-key $offset
     */
    final public function offsetUnset(mixed $offset): never
    {
        if ($this->offsetExists($offset)) {
            throw new \Error('Cannot unset readonly property ' . $this->qualifyOffset($offset));
        }

        throw new \Error('Cannot unset dynamic property ' . $this->qualifyOffset($offset));
    }

    /**
     * @param array-key $offset
     */
    final protected function qualifyOffset(mixed $offset): string
    {
        return sprintf('%s::$%s', static::class, strval($offset));
    }

    /**
     * @return array<array-key, TValue>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
