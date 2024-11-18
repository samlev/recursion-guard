<?php

declare(strict_types=1);

namespace Tests\Support\Documentation\LinkedList;

use RecursionGuard\Recurser;

class LinkedList
{
    protected ?LinkedList $next = null;

    public function __construct(
        public readonly int $id,
    ) {
        //
    }

    public function next(LinkedList $next): void
    {
        $this->next = $next;
    }

    public function children(): array
    {
        return Recurser::call(
            fn () => array_filter([$this->next?->id, ...($this->next?->children() ?? [])]),
            [],
        );
    }
}
