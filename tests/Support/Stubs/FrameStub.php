<?php

declare(strict_types=1);

namespace Tests\Support\Stubs;

use RecursionGuard\Data\Frame;
use Tests\Support\StubSpy;

readonly class FrameStub extends Frame
{
    public static function make(Frame|array $from = []): static
    {
        StubSpy::instance()->call(__METHOD__, [$from]);

        return parent::make($from);
    }
}
