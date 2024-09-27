<?php

declare(strict_types=1);

namespace RecursionGuard\Data;

use ArrayAccess;
use JsonSerializable;
use RecursionGuard\Support\ArrayWritesForbidden;

/**
 * @phpstan-import-type TraceArray from Trace
 * @phpstan-import-type FrameArray from Frame
 *
 * @implements ArrayAccess<'line'|'class'|'function'|'file'|'object'|'signature', int|string|object|null>
 */
readonly class RecursionContext implements ArrayAccess, JsonSerializable
{
    use ArrayWritesForbidden;

    public function __construct(
        public string $file = '',
        public string $class = '',
        public string $function = '',
        public int $line = 0,
        public object|null $object = null,
    ) {
        //
    }

    public function signature(): string
    {
        return sprintf(
            '%s:%s%s',
            $this->file,
            $this->class ? ($this->class . '@') : '',
            $this->function ?: $this->line,
        );
    }

    /**
     * @param int|string $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return match ($offset) {
            'file', 'line', 'class', 'function', 'signature' => true,
            'object' => $this->$offset !== null,
            default => false,
        };
    }

    /**
     * @param int|string $offset
     * @return ($offset is 'line' ? int : ($offset is 'object' ? object|null : string|null))
     */
    public function offsetGet(mixed $offset): mixed
    {
        return match ($offset) {
            'file', 'line', 'class', 'function', 'object' => $this->$offset,
            'signature' => $this->signature(),
            default => null,
        };
    }

    /**
     * @return array{file: string, class: string, function: string, line: int, object: object|null, signature: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'file' => $this->file,
            'class' => $this->class,
            'function' => $this->function,
            'line' => $this->line,
            'object' => $this->object,
            'signature' => $this->signature(),
        ];
    }
}
