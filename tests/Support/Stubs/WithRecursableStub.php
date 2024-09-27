<?php

declare(strict_types=1);

namespace Tests\Support\Stubs;

use RecursionGuard\Recursable;
use RecursionGuard\Support\WithRecursable;
use Tests\Support\ExposesProtectedMethods;

class WithRecursableStub
{
    use WithRecursable;
    use ExposesProtectedMethods;

    public function __construct(
        public ?string $message = 'default',
        public int $code = 0,
        public ?\Throwable $previous = null
    ) {
        //
    }

    protected static function makeMessage(Recursable $recursable): string
    {
        return 'from: ' . $recursable->signature;
    }
}
