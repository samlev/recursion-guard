<?php

declare(strict_types=1);

namespace RecursionGuard\Data;

use ArrayAccess;
use JsonSerializable;
use RecursionGuard\Support\ArrayWritesForbidden;

/**
 * @phpstan-import-type FrameArray from Frame
 * @phpstan-type TraceArray array<int, FrameArray|Frame>
 *
 * @implements ArrayAccess<int, Frame>
 */
readonly class Trace implements ArrayAccess, JsonSerializable
{
    use ArrayWritesForbidden;

    /**
     * @var Frame[]
     */
    public array $frames;

    /**
     * @param TraceArray $frames
     */
    public function __construct(
        array $frames = [],
    ) {
        $this->frames = array_map(Frame::make(...), $frames);
    }

    /**
     * @param Trace|TraceArray $trace
     * @return self
     */
    public static function make(Trace|array $trace): self
    {
        return new self($trace instanceof Trace ? $trace->frames : $trace);
    }

    /**
     * @param bool $include_empty
     * @return Frame[]
     */
    public function frames(bool $include_empty = false): array
    {
        return $include_empty
            ? $this->frames
            : array_values(array_filter($this->frames, fn (Frame $frame) => !$frame->empty()));
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->frames);
    }

    /**
     * @return bool
     */
    public function empty(): bool
    {
        return count($this->frames()) === 0;
    }
    /**
     * @param int $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->frames[$offset]);
    }

    /**
     * @param int $offset
     * @return Frame|null
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->frames[$offset] ?? null;
    }

    /**
     * @return array<int, Frame>
     */
    public function jsonSerialize(): array
    {
        return $this->frames;
    }
}
