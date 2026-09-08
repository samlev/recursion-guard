<?php

declare(strict_types=1);

use RecursionGuard\Recurser;
use Tests\Support\Stubs\RecurserStub;
use Tests\Support\Spy;
use Tests\TestCase;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;

$flush = function () {
    Recurser::flush();
    RecurserStub::flush();
    Spy::flush();
};

beforeAll($flush);
afterEach($flush);

pest()->extend(TestCase::class, VerifiesDoubles::class)->in('Feature', 'Unit');
