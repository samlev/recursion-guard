<?php

declare(strict_types=1);

namespace RecursionGuard\Data;

use Countable;
use RecursionGuard\Exception\InvalidTraceException;
use RecursionGuard\Recurser;

/**
 * @phpstan-import-type FrameArray from Frame
 * @phpstan-type TraceArray Frame[]|FrameArray[]
 *
 * @extends BaseData<int, Frame>
 */
readonly class Trace extends BaseData implements Countable
{
    /** @var Frame[] */
    public array $frames;

    /**
     * @param array<array-key, mixed> $frames
     */
    final public function __construct(
        array $frames = [],
    ) {
        array_map(
            fn (mixed $frame) => $frame instanceof Frame || throw InvalidTraceException::make($frames),
            $frames,
        );

        /** @var Frame[] $frames */
        $this->frames = array_values($frames);
    }

    /**
     * @param Trace|TraceArray $frames
     */
    public static function make(Trace|array $frames = []): static
    {
        return new static(
            $frames instanceof self
                ? $frames->frames
                : array_map(Recurser::instance()->factory->makeFrame(...), $frames)
        );
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
        return empty($this->frames());
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
     * @return Frame[]
     */
    public function jsonSerialize(): array
    {
        return $this->frames;
    }
}
