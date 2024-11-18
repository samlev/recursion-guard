<?php

declare(strict_types=1);

namespace Tests\Support\Data;

readonly class ReadonlyNullableClass
{
    public null $null;
    public ?string $string;
    public ?int $int;
    public ?bool $bool;
    public ?array $array;
}
