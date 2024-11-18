<?php

declare(strict_types=1);

namespace Tests\Support\Data;

class PromotedMixedProperties
{
    public null $null;
    protected string $string = 'foo';
    public array $array;
    protected PromotedProperties $object;

    public function __construct(
        public int $int = 42,
        private bool $bool = true,
        array $array = ['bing' => 'bang'],
        ?PromotedProperties $object = null,
    ) {
        $this->array = $array;
        $this->object = $object ?? new PromotedProperties(int: $this->int, bool: $this->bool, array: $array);
    }
}
