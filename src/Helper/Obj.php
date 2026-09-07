<?php

declare(strict_types=1);

namespace RecursionGuard\Helper;

class Obj
{
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
