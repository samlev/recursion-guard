<?php

declare(strict_types=1);

namespace RecursionGuard;

if (! function_exists('array_only')) {
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
            array_flip(array_unique(array_filter(
                array_values($keys),
                fn ($v) => is_int($v) || is_string($v), // @phpstan-ignore function.alreadyNarrowedType
            )))
        );
    }
}
