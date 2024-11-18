<?php

declare(strict_types=1);

namespace RecursionGuard\Data;

/**
 * @phpstan-type FrameArray array{
 *     'file'?: string,
 *     'class'?: string,
 *     'function'?: string,
 *     'line'?: int,
 *     'object'?: ?object,
 * }
 *
 * @extends BaseData<'file'|'line'|'class'|'function'|'object', int|string|object|null>
 */
readonly class Frame extends BaseData
{
    final public function __construct(
        public string $file = '',
        public string $class = '',
        public string $function = '',
        public int $line = 0,
        public object|null $object = null,
    ) {
        //
    }

    /**
     * @param Frame|FrameArray $from
     * @return static
     */
    public static function make(Frame|array $from = []): static
    {
        /** @var FrameArray $from */
        $from = (
            $from instanceof Frame
                ? $from->jsonSerialize()
                : self::only($from, ['file', 'class', 'function', 'line', 'object'])
        );

        return new static(...$from);
    }

    /**
     * Get a subset of the items from the given array.
     *
     * @template TArray of array<array-key, mixed>
     *
     * @param TArray $array
     * @param array<int, array-key> $keys
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
                        fn ($v) => is_int($v) || is_string($v), // @phpstan-ignore function.alreadyNarrowedType
                    )
                )
            )
        );
    }
}
