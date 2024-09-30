<?php

declare(strict_types=1);

namespace Tests\Support\Stubs;

use RecursionGuard\Data\Frame;
use RecursionGuard\Data\Trace;
use Tests\Support\StubSpy;

readonly class TraceStub extends Trace
{
    public static function make(Trace|array $frames = []): static
    {
        StubSpy::instance()->call(__METHOD__, [$frames]);

        return parent::make($frames);
    }
}
