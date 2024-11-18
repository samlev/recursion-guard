<?php

declare(strict_types=1);

namespace Tests\Support\Data;

use RecursionGuard\Data\BaseData;
use stdClass;

readonly class DefaultPropertiesData extends BaseData
{
    public function __construct(
        public null $null = null,
        public string $string = 'foo',
        public int $int = 42,
        public bool $bool = true,
        public array $array = [1, 2, 3],
        public object $object = new stdClass(),
    ) {
        //
    }
}
