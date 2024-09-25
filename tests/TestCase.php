<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Tests\Support\StubSpy;

abstract class TestCase extends BaseTestCase
{
    public ?StubSpy $spy = null;

    protected function spy(): StubSpy
    {
        return $this->spy ??= StubSpy::make($this);
    }
}
