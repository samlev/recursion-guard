<?php

declare(strict_types=1);

namespace RecursionGuard\Helper;

class Arr
{
    /**
     * Get a subset of the items from the given array.
     *
     * @template TArray of array<array-key, mixed>
     *
     * @param TArray $array
     * @param array<array-key, mixed> $keys
     * @return TArray
     */
    public static function only(array $array, array $keys): array
    {
        return array_intersect_key(
            $array,
            array_flip(
                array_unique(
                    array_filter(
                        array_values($keys),
                        fn ($v): bool => is_int($v) || is_string($v),
                    )
                )
            )
        );
    }
}
