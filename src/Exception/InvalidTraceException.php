<?php

declare(strict_types=1);

namespace RecursionGuard\Exception;

use InvalidArgumentException;
use RecursionGuard\Data\Frame;
use Throwable;

class InvalidTraceException extends InvalidArgumentException
{
    /** @var array<int, mixed> */
    protected array $invalidTrace;
    /** @var array<int, mixed> */
    protected array $invalidFrames = [];

    /**
     * @param array<array-key, mixed> $from
     */
    public static function make(
        array $from,
        ?string $message = null,
        int $code = 0,
        ?Throwable $previous = null
    ): self {
        $invalid = array_filter($from, fn ($frame) => !($frame instanceof Frame));

        $message = $message ?: sprintf(
            'Invalid trace frame(s) provided: %s',
            json_encode($invalid),
        );

        $exception = new self($message, $code, $previous);

        $exception->invalidTrace = $from;
        $exception->invalidFrames = $invalid;

        return $exception;
    }

    /**
     * @return array<int, mixed>
     */
    public function getInvalidTrace(): array
    {
        return $this->invalidTrace;
    }

    /**
     * @return array<int, mixed>
     */
    public function getInvalidFrames(): array
    {
        return $this->invalidFrames;
    }
}
