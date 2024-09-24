<?php

declare(strict_types=1);

namespace RecursionGuard\Exception;

use RecursionGuard\Recursable;
use RecursionGuard\Support\WithRecursable;

final class RecursionException extends \RuntimeException
{
    /**
     * @use WithRecursable<mixed>
     */
    use WithRecursable;

    /**
     * @param Recursable<mixed> $recursable
     * @return string
     */
    protected static function makeMessage(Recursable $recursable): string
    {
        return sprintf(
            match (true) {
                $recursable->finished() => 'Call stack for [%s] has completed.',
                $recursable->recursing() => 'Callback for [%s] has been called while resolving return value.',
                $recursable->started() => 'Callback for [%s] has been called recursively.',
                default => 'Call stack for [%s] has not commenced.',
            },
            $recursable->signature,
        );
    }
}
