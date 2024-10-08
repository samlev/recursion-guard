<?php

declare(strict_types=1);

namespace Tests\Support\Stubs;

use RecursionGuard\Recursable;
use Tests\Support\ExposesProtectedProperties;
use Tests\Support\ExposesStaticMethods;
use Tests\Support\SetsState;

class RecursableStub extends Recursable
{
    use ExposesStaticMethods;
    use ExposesProtectedProperties;
    use SetsState;
}
