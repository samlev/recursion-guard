<?php

use RecursionGuard\Recurser;
use Tests\Support\StubSpy;
use Tests\TestCase;

beforeAll(function () {
    Recurser::flush();
    StubSpy::flush();
});

pest()->extend(TestCase::class)->in('Feature', 'Unit');

afterEach(function () {
    Recurser::flush();
    StubSpy::flush();
});
