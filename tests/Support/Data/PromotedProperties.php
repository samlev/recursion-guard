<?php

declare(strict_types=1);

namespace Tests\Support\Data;

use stdClass;

class PromotedProperties
{
    public function __construct(
        public null $null = null,
        public string $string = 'foo',
        public int $int = 42,
        public bool $bool = true,
        public array $array = [],
        public object $object = new stdClass(),
    ) {
        //
    }
}
