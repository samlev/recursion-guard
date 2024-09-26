# Use within classes/objects

When you use the recursion guard within the context of an instantiated object, the protection against recursion is
linked exclusively to the _actual instance_ of the object that a method is called on. This means that you can call the
same method on different objects of the same class within the same call stack, and each one will have its own recursion
guard.
```php
class LinkedList 
{
    // ...
    public function children(): array
    {
        return RecursionGuard\Recurser::call(
            fn () => array_filter([$this->next?->id, ...$this->next?->children()]),
            [],
        );
    }
}

$head = new LinkedList(1);
$body = new LinkedList(2);
$tail = new LinkedList(3);

$head->children(); // []
$body->children(); // []
$tail->children(); // []

// link them together
$head->next($body);
$body->next($tail);

$head->children(); // [2, 3]
$body->children(); // [3]
$tail->children(); // []

// create a potential infinite loop...
$tail->next($head);

$head->bloodline(); // [2, 3, 1]
$body->bloodline(); // [3, 1, 2]
$tail->bloodline(); // [1, 2, 3]
```
As each instance calls `children()` on the next element on the list, the recursion guard prevents the last item on the
list from calling `children()` recursively on the first. This takes effect no matter where in the list you start. To
help visualize the recursive call stack, it looks something like this:
this:
```php
$head->children():
    $head@children()::$recurseWith = []
    $callback():
        [$body->id, ...$body->children()] // [2, ...$body->children()]
        $body->children():
            $body@children()::$recurseWith = []
            $callback():
                [$tail->id, ...$tail->children()] // [3, ...$tail->children()]
                $tail->children():
                    $tail@children()::$recurseWith = []
                    $callback():
                        [$head->id, ...$head->children()] // [1, ...$head->children()]
                        $head->children():
                            return $head@children()::$recurseWith // []
                        return [1, ...[]] // [1]
                return [3, ...[1]] // [3, 1]
        return [2, ...[3, 1]] // [2, 3, 1]
```

## Explicitly setting the context
In the event that you want to tie the recursion protection to a _specific_ object, no matter which object is _actually_
being called, you can pass an object as a third parameter, or with the parameter name `for:`.
```php
class TravellingSalesman 
{   
    public function shortestPath(Location $from, Location $to, int $travelled = 0, &$trail = []): int
    {
        return $travelled + RecursionGuard\Recurser::call(
            function () use ($from, $to, $travelled, &$trail) {
                $steps = [];
                foreach ($from->connections as $step) {
                    if ($step->location === $to) {
                        $trail[] = $step;
                        
                        return $step->distance;
                    }
                    $steps[] = [
                        'step' => $step,
                        'total' => $this->shortestPath($step->location, $to, $step->distance, $trail),
                    ]
                }
                usort($steps, fn ($a, $b) => $a['total'] <=> $b['total']);
                $next = array_shift($steps);
                array_unshift($trail, $next['location']);
                return $next['travelled'];
            },
            // Penalise trying to travel from this location again
            9999999,
            for: $from,
        );
    }
}
```
In this example the recursion protection is attached to the `Location` object, instead of the `TravellingSalesman` which
would normally be the object getting tracked. Without setting the object to the `Location`, the recursion protection
would prevent travelling along more than a single step.
