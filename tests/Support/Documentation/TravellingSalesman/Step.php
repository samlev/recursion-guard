<?php

declare(strict_types=1);

namespace Tests\Support\Documentation\TravellingSalesman;

class Step
{
    public readonly string $name;

    public function __construct(
        public readonly Location $from,
        public readonly Location $to,
        public readonly int $distance,
    ) {
        $this->name = sprintf('%s -> %s', $this->from->name, $this->to->name);
    }
}
