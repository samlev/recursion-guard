<?php

declare(strict_types=1);

namespace Tests\Support\Data;

class NullableProperties
{
    public ?string $string;
    public ?int $int;
    public ?bool $bool;
    public ?array $array;
    private ?object $object;
}
