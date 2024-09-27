<?php

declare(strict_types=1);

use RecursionGuard\Recurser;
use Tests\Support\Stubs\RecurserStub;
use Tests\Support\StubSpy;
use Tests\TestCase;

$flush = function () {
    Recurser::flush();
    RecurserStub::flush();
    StubSpy::flush();
};

beforeAll($flush);
afterEach($flush);

pest()->extend(TestCase::class)->in('Feature', 'Unit');
