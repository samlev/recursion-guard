<?php

declare(strict_types=1);

namespace Tests\Support\Stubs;

use RecursionGuard\Data\Frame;
use RecursionGuard\Data\Trace;
use Tests\Support\Spy;

readonly class TraceStub extends Trace
{
    public static function make(Trace|array $frames = []): static
    {
        try {
            return parent::make($frames);
        } finally {
            Spy::instance()->call(__METHOD__, [$frames]);
        }
    }
}
