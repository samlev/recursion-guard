<?php

declare(strict_types=1);

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
        return RecursionGuard\Recurser::call(
            fn () => array_filter([$this->next?->id, ...($this->next?->children() ?? [])]),
            [],
        );
    }
}

it('returns empty set if there are no children', function () {
    $head = new LinkedList(1);
    $body = new LinkedList(2);
    $tail = new LinkedList(3);

    expect($head->children())->toEqual([])
        ->and($body->children())->toEqual([])
        ->and($tail->children())->toEqual([]);
});

it('does not include self in children with a list', function () {
    $head = new LinkedList(1);
    $body = new LinkedList(2);
    $tail = new LinkedList(3);

    $head->next($body);
    $body->next($tail);

    expect($head->children())->toEqual([2, 3])
        ->and($body->children())->toEqual([3])
        ->and($tail->children())->toEqual([]);
});

it('includes self in children with a circular list', function () {
    $head = new LinkedList(1);
    $body = new LinkedList(2);
    $tail = new LinkedList(3);

    $head->next($body);
    $body->next($tail);
    $tail->next($head);

    expect($head->children())->toEqual([2, 3, 1])
        ->and($body->children())->toEqual([3, 1, 2])
        ->and($tail->children())->toEqual([1, 2, 3]);
});
