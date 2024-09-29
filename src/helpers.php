<?php

declare(strict_types=1);

namespace RecursionGuard;

if (! function_exists('array_only')) {
    /**
     * Get a subset of the items from the given array.
     *
     * @param array<array-key, mixed> $array
     * @param array-key[] $keys
     * @return array
     */
    function array_only(array $array, array $keys): array
    {
        return array_intersect_key(
            $array,
            array_flip(array_unique(array_filter(
                array_values($keys),
                fn ($v) => is_int($v) || is_string($v),
            )))
        );
    }
}
