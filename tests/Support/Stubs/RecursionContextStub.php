<?php

declare(strict_types=1);

namespace Tests\Support\Stubs;

use RecursionGuard\Data\RecursionContext;
use RecursionGuard\Data\Trace;
use Tests\Support\StubSpy;

/**
 * @phpstan-import-type Frame from RecursionContext
 * @phpstan-import-type Trace from RecursionContext
 */
readonly class RecursionContextStub extends RecursionContext
{
    /**
     * @param Trace $trace
     */
    public static function fromTrace(array|Trace $trace): RecursionContext
    {
        StubSpy::instance()->call(__METHOD__, [$trace]);

        return parent::fromTrace($trace);
    }

    public static function fromCallable(callable|array $callable): RecursionContext
    {
        StubSpy::instance()->call(__METHOD__, [$callable]);

        return parent::fromCallable($callable);
    }
}
