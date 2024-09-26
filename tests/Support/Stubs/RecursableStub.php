<?php

declare(strict_types=1);

namespace Tests\Support\Stubs;

use RecursionGuard\Recursable;
use Tests\Support\ExposesProtectedProperties;
use Tests\Support\ExposesStaticMethods;

class RecursableStub extends Recursable
{
    use ExposesStaticMethods;
    use ExposesProtectedProperties;

    public function state(...$params): static
    {
        foreach ($params as $prop => $value) {
            $this->{$prop} = $value;
        }

        return $this;
    }
}
