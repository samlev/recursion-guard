<?php

declare(strict_types=1);

namespace RecursionGuard\Exception;

use InvalidArgumentException;
use RecursionGuard\Data\Trace;
use Throwable;

final class InvalidContextException extends InvalidArgumentException
{
    public static function make(
        mixed $from,
        ?string $message = null,
        int $code = 0,
        ?Throwable $previous = null
    ): self {
        $message = $message ?: match (true) {
            is_array($from) && $from => sprintf('Invalid backtrace provided: %s', json_encode($from)),
            $from instanceof Trace && $from->empty(), is_array($from) => 'Empty backtrace provided.',
            default => 'Invalid context provided.',
        };

        return new self($message, $code, $previous);
    }
}
