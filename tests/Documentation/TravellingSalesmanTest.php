<?php

declare(strict_types=1);

use Tests\Support\Documentation\TravellingSalesman\Location;
use Tests\Support\Documentation\TravellingSalesman\Salesman;

it('finds shortest route', function (Location $from, Location $to, array $expectedTrail) {
    $travellingSalesman = new Salesman($from, $to);

    $trail = $travellingSalesman->route();

    expect(array_sum(array_column($trail, 'distance')))->toEqual(array_sum(array_column($expectedTrail, 'distance')))
        ->and(array_column($trail, 'name'))->toEqual(array_column($expectedTrail, 'name'));
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
