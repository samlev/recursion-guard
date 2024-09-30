<?php

declare(strict_types=1);

namespace Tests\Support\Stubs;

use RecursionGuard\Data\RecursionContext;

/**
 * @phpstan-import-type Frame from RecursionContext
 * @phpstan-import-type Trace from RecursionContext
 */
readonly class RecursionContextStub extends RecursionContext
{
    //
}
