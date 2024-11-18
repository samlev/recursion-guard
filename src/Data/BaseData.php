<?php

declare(strict_types=1);

namespace RecursionGuard\Data;

use ArrayAccess;
use JsonSerializable;
use RecursionGuard\Support\ArrayWritesForbidden;

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

        foreach (self::defaults($this) as $property => $default) {
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

    /**
     * Gets the properties of a class that have default values.
     *
     * @param object|class-string $class
     * @return array<string, mixed>
     * @throws \ReflectionException
     */
    public static function defaults(object|string $class): array
    {
        $defaults = [];

        $class = new \ReflectionClass($class);

        foreach ($class->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }

            if ($property->getType()?->allowsNull()) {
                $defaults[$property->getName()] = null;
            }

            if ($property->hasDefaultValue()) {
                $defaults[$property->getName()] = $property->getDefaultValue();
            } elseif ($property->isPromoted()) {
                $parameter = array_values(
                    array_filter(
                        $class->getConstructor()?->getParameters() ?? [],
                        fn (\ReflectionParameter $parameter) => $parameter->getName() === $property->getName(),
                    )
                )[0] ?? null;

                if ($parameter?->isDefaultValueAvailable()) {
                    $defaults[$property->getName()] = $parameter->getDefaultValue();
                } elseif ($parameter?->allowsNull()) {
                    $defaults[$property->getName()] = null;
                }
            }
        }

        return $defaults;
    }
}
