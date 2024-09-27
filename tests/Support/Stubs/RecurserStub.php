<?php

declare(strict_types=1);

namespace Tests\Support\Stubs;

use RecursionGuard\Recurser;
use Tests\Support\ExposesProtectedMethods;
use Tests\Support\ExposesProtectedProperties;
use Tests\Support\ExposesStaticMethods;
use Tests\Support\SetsState;

class RecurserStub extends Recurser
{
    use ExposesStaticMethods;
    use ExposesProtectedProperties;
    use ExposesProtectedMethods;
    use SetsState;
}
