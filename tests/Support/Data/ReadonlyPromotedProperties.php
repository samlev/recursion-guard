<?php

declare(strict_types=1);

namespace Tests\Support\Data;

class ReadonlyPromotedProperties
{
    public function __construct(
        public ?string $string,
        public readonly int $int = 42,
        public readonly bool $bool = true,
        public readonly array $array = [],
        public readonly PromotedProperties $object = new PromotedProperties(string: 'bar', int: 99, bool: false),
    ) {
        //
    }
}
