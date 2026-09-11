<?php

declare(strict_types=1);

namespace Tests;

use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase as BaseTestCase;
use RecursionGuard\Recurser;
use Tests\Support\VerifiesSpies;

abstract class TestCase extends BaseTestCase
{
    use VerifiesDoubles;
    use VerifiesSpies;

    #[After]
    #[Before]
    public function flushRecurser(): void
    {
        Recurser::flush();
    }
}
