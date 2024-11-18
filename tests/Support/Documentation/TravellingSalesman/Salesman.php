<?php

declare(strict_types=1);

namespace Tests\Support\Documentation\TravellingSalesman;

use RecursionGuard\Recurser;

class Salesman
{
    public function __construct(
        public Location $current,
        public Location $destination,
    ) {
        //
    }

    /**
     * @return Step[]
     */
    public function route(): array
    {
        $path = $this->shortestPath($this->current, $this->destination);

        return $path;
    }

    public function shortestPath(Location $from, Location $to, $trail = []): array
    {
        return Recurser::call(
            function () use ($from, $to, $trail) {
                $previous = end($trail) ?: null;
                $final = $trail;
                $shortest = 0;

                foreach ($from->connections as $step) {
                    // Don't travel back to the previous location
                    if ($step->to === $previous?->from) {
                        continue;
                    }

                    $path = [...$trail, $step];

                    // if we're not at the end, keep recursing
                    if ($step->to !== $to) {
                        $path = $this->shortestPath($step->to, $to, $path);
                    }

                    $distance = array_sum(array_column($path, 'distance'));

                    if (!$shortest || $distance < $shortest) {
                        $shortest = $distance;
                        $final = $path;
                    }
                }

                return $final;
            },
            // We have visited this location before, so return a distance that is too long
            [new Step($from, $to, 9999999)],
            for: $from,
        );
    }
}
