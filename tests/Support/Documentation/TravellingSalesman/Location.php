<?php

declare(strict_types=1);

namespace Tests\Support\Documentation\TravellingSalesman;

class Location
{
    /**
     * @var array<string, Step>
     */
    public array $connections = [];

    public function __construct(
        public readonly string $name,
    ) {
        //
    }

    public function connectTo(Location $location, int $distance): void
    {
        if (isset($this->connections[$location->name])) {
            return;
        }
        $this->connections[$location->name] = new Step($this, $location, $distance);
        $location->connectTo($this, $distance);
    }
}
