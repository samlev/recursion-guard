<?php

declare(strict_types=1);

namespace Tests\Support;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use Tests\TestCase;

/**
 * @mixin TestCase
 */
trait VerifiesSpies
{
    public ?Spy $spy = null;

    #[Before]
    final public function prepareSpies(): void
    {
        Spy::flush();
    }

    protected function spy(): Spy
    {
        return $this->spy ??= Spy::make($this);
    }

    #[After]
    final public function verifySpies(): void
    {
        $this->spy?->assert();
    }
}
