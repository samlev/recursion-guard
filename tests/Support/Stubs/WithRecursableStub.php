<?php

declare(strict_types=1);

namespace Tests\Support\Stubs;

use RecursionGuard\Recursable;
use RecursionGuard\Support\WithRecursable;

class WithRecursableStub
{
    use WithRecursable;

    public function __construct(
        public ?string $message = 'default',
        public int $code = 0,
        public ?\Throwable $previous = null
    ) {
        //
    }

    public function forward_withRecursable(Recursable $recursable): static
    {
        return $this->withRecursable($recursable);
    }

    protected static function makeMessage(Recursable $recursable): string
    {
        return 'from: ' . $recursable->signature;
    }
}
