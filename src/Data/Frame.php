<?php

declare(strict_types=1);

namespace RecursionGuard\Data;

use ArrayAccess;
use JsonSerializable;
use RecursionGuard\Support\ArrayWritesForbidden;

/**
 * @phpstan-type FrameArray array{
 *     'file'?: string,
 *     'class'?: string,
 *     'function'?: string,
 *     'line'?: int,
 *     'object'?: ?object,
 * }
 *
 * @implements ArrayAccess<'file'|'line'|'class'|'function'|'object', int|string|object|null>
 */
readonly class Frame implements ArrayAccess, JsonSerializable
{
    use ArrayWritesForbidden;

    public function __construct(
        public string $file = '',
        public ?string $class = null,
        public ?string $function = null,
        public int $line = 0,
        public object|null $object = null,
    ) {
        //
    }

    public function empty(): bool
    {
        return
            $this->file === ''
            && empty($this->class)
            && empty($this->function)
            && $this->line === 0
            && $this->object === null;
    }

    /**
     * @param int|string $offset
     * @return ($offset is 'file'|'line'|'class'|'function'|'object' ? bool : false)
     */
    public function offsetExists(mixed $offset): bool
    {
        return match ($offset) {
            'file', 'line' => true,
            'class', 'function', 'object' => $this->$offset !== null,
            default => false,
        };
    }

    /**
     * @param int|string $offset
     * @return ($offset is 'file'
     *          ? string
     *          : ($offset is 'line'
     *              ? int
     *              : ($offset is 'object'
     *                  ? object|null
     *                  : ($offset is 'line'|'class'|'function' ? string|null : null))))
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->offsetExists($offset) ? $this->$offset : null;
    }

    /**
     * @return array{file: string, class: string|null, function: string|null, line: int, object: object|null}
     */
    public function jsonSerialize(): array
    {
        return [
            'file' => $this->file,
            'function' => $this->function,
            'class' => $this->class,
            'line' => $this->line,
            'object' => $this->object,
        ];
    }
}
