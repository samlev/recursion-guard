<?php

declare(strict_types=1);

namespace RecursionGuard;

if (!function_exists('array_only')) {
    /**
     * Get a subset of the items from the given array.
     *
     * @template TArray of array<array-key, mixed>
     *
     * @param TArray $array
     * @param array<int, array-key> $keys
     * @return TArray
     */
    function array_only(array $array, array $keys): array
    {
        return array_intersect_key(
            $array,
            array_flip(
                array_unique(
                    array_filter(
                        array_values($keys),
                        fn ($v) => is_int($v) || is_string($v), // @phpstan-ignore function.alreadyNarrowedType
                    )
                )
            )
        );
    }
}

if (!function_exists('class_defaults')) {
    /**
     * Gets the properties of a class that have default values.
     *
     * @param object|class-string $class
     * @return array<string, mixed>
     * @throws \ReflectionException
     */
    function class_defaults(object|string $class): array
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
