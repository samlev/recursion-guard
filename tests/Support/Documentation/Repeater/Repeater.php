<?php

declare(strict_types=1);

namespace Tests\Support\Documentation\Repeater;

use RecursionGuard\Recurser;

class Repeater
{
    public function __construct(
        public readonly int $times,
    ) {
        //
    }

    /**
     * @param string|callable(): string $repeat
     * @param string $default
     * @return string
     */
    public function __invoke(string|callable $repeat, string $default): string
    {
        return Recurser::call(
            fn () => sprintf('%d: [%s]', $this->times, implode(', ', array_fill(0, $this->times, (is_callable($repeat) ? $repeat() : $repeat)))),
            sprintf('%d (default): [%s]', $this->times, implode(', ', array_fill(0, $this->times, $default))),
        );
    }
}
