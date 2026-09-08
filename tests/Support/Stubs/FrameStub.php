<?php

declare(strict_types=1);

namespace Tests\Support\Stubs;

use RecursionGuard\Data\Frame;
use Tests\Support\Spy;

readonly class FrameStub extends Frame
{
    public static function make(Frame|array $from = []): static
    {
        try {
            return parent::make($from);
        } finally {
            Spy::instance()->call(__METHOD__, [$from]);
        }
    }
}
