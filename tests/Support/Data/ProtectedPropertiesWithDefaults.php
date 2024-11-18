<?php

declare(strict_types=1);

namespace Tests\Support\Data;

class ProtectedPropertiesWithDefaults
{
    protected null $null = null;
    protected string $string = 'foo';
    protected int $int = 42;
    protected bool $bool = true;
    protected array $array = [];
    protected object $object;
}
