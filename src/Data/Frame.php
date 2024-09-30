<?php

declare(strict_types=1);

namespace RecursionGuard\Data;

use function RecursionGuard\array_only;

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
                : array_only($from, ['file', 'class', 'function', 'line', 'object'])
        );

        return new static(...$from);
    }
}
