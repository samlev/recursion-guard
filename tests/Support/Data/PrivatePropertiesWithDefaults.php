<?php

declare(strict_types=1);

namespace Tests\Support\Data;

class PrivatePropertiesWithDefaults
{
    private null $null = null;
    private string $string = 'foo';
    private int $int = 42;
    private bool $bool = true;
    private array $array = [];
    private object $object;
}
