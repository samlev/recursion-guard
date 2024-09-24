<?php

declare(strict_types=1);

namespace RecursionGuard\Support;

trait ArrayWritesForbidden
{
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \RuntimeException(static::class . ' is read-only');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \RuntimeException(static::class . ' is read-only');
    }
}
