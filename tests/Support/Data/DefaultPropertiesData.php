<?php

declare(strict_types=1);

namespace Tests\Support\Data;

use RecursionGuard\Data\BaseData;
use stdClass;

final readonly class DefaultPropertiesData extends BaseData
{
    public function __construct(
        public null $null = null,
        public string $string = 'foo',
        public int|float $number = 42,
        public bool $bool = true,
        public array $array = [1, 2, 3],
        public object|string $class = new stdClass(),
    ) {
        //
    }
}
