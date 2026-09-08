<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Tests\Support\Spy;
use Tests\Support\VerifiesSpies;

abstract class TestCase extends BaseTestCase
{
    use VerifiesSpies;
}
