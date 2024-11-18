<?php

declare(strict_types=1);

namespace Tests\Support\Data;

class NullablePropertiesWithDefaults
{
    public ?string $string = 'foo';
    public ?int $int = 42;
    public ?bool $bool = true;
    public ?array $array = [];
}
