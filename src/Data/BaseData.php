<?php

declare(strict_types=1);

namespace RecursionGuard\Data;

use ArrayAccess;
use JsonSerializable;
use RecursionGuard\Support\ArrayWritesForbidden;

use function RecursionGuard\class_defaults;

/**
 * @template TArrayKey of array-key
 * @template TValue of mixed
 *
 * @implements ArrayAccess<TArrayKey, TValue>
 */
abstract readonly class BaseData implements ArrayAccess, JsonSerializable
{
    use ArrayWritesForbidden;

    public function empty(): bool
    {
        $vars = get_object_vars($this);

        foreach (class_defaults($this) as $property => $default) {
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
     * @return array<array-key, TValue>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
