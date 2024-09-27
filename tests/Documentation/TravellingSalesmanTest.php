<?php

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

class TravellingSalesman
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
        return RecursionGuard\Recurser::call(
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

test('it finds shortest route', function (Location $from, Location $to, array $expectedTrail) {
    $travellingSalesman = new TravellingSalesman($from, $to);

    $trail = $travellingSalesman->route();

    expect(array_sum(array_column($trail, 'distance')))->toEqual(array_sum(array_column($expectedTrail, 'distance')))
        ->and(array_column($trail, 'name'))->toEqual( array_column($expectedTrail, 'name'));
})->with([
    'straight' => function () {
        $a = new Location('A');
        $b = new Location('B');
        $c = new Location('C');

        $a->connectTo($b, 1);
        $b->connectTo($c, 2);

        return [$a, $c, [$a->connections['B'], $b->connections['C']]];
    },
    'clockwise loop' => function () {
        $a = new Location('A');
        $b = new Location('B');
        $c = new Location('C');

        $a->connectTo($b, 1);
        $b->connectTo($c, 2);
        $c->connectTo($a, 4);

        return [$a, $c, [$a->connections['B'], $b->connections['C']]];
    },
    'anti-clockwise loop' => function () {
        $a = new Location('A');
        $b = new Location('B');
        $c = new Location('C');

        $a->connectTo($b, 1);
        $b->connectTo($c, 2);
        $c->connectTo($a, 2);

        return [$a, $c, [$a->connections['C']]];
    },
    'loop to self' => function () {
        $a = new Location('A');
        $b = new Location('B');
        $c = new Location('C');

        $a->connectTo($b, 1);
        $b->connectTo($c, 2);
        $c->connectTo($a, 4);

        return [$a, $a, [$a->connections['B'], $b->connections['C'], $c->connections['A']]];
    },
    'complex path' => function () {
        $a = new Location('A');
        $b = new Location('B');
        $c = new Location('C');
        $d = new Location('D');
        $e = new Location('E');
        $f = new Location('F');

        $a->connectTo($b, 1);
        $a->connectTo($d, 4);
        $a->connectTo($f, 10);

        $b->connectTo($c, 2);
        $b->connectTo($d, 2);

        $c->connectTo($d, 3);

        $d->connectTo($e, 1);
        $d->connectTo($f, 3);

        $e->connectTo($f, 1);

        return [$a, $f, [$a->connections['B'], $b->connections['D'], $d->connections['E'], $e->connections['F']]];
    },
]);
