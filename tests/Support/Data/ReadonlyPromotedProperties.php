<?php

declare(strict_types=1);

namespace Tests\Support\Data;

class ReadonlyPromotedProperties
{
    public function __construct(
        public ?string $string,
        readonly public int $int = 42,
        readonly public bool $bool = true,
        readonly public array $array = [],
        readonly public PromotedProperties $object = new PromotedProperties(string: 'bar', int: 99, bool: false),
    ) {
        //
    }
}
