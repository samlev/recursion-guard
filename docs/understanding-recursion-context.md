# Understanding Recursion Context
There are two parts to the context which are used to prevent recursion:
* The object that the function is being called on
* The signature of the function being called

The recursion guard keeps a stack of results for each object that is currently being guarded. Each stack is keyed by a
hash of the unique function signature. This means that if an object is being guarded for one method, other methods for
that object may not yet be guarded, and the same method on other objects may also not be guarded. When the callback
for a guarded method returns, that guard is automatically released from the stack.

## Context Object
Each guarded function is linked to an object so that the protection doesn't interfere with other instances of the same
object. This means that you can call the same guarded method on different objects of the same class within the same call
stack, and each object will have its own guard, and will call the callback within its own context.

[See the tests](../tests/Documentation/LinkedListTest.php)

```php
class LinkedList 
{
    // ...
    public function children(): array
    {
        return RecursionGuard\Recurser::call(
            fn () => array_filter([$this->next?->id, ...($this->next?->children() ?? [])]),
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

$head->children(); // [2, 3, 1]
$body->children(); // [3, 1, 2]
$tail->children(); // [1, 2, 3]
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

### Context outside of objects
When you use the recursion guard in a global function, closure, or a static function on a class that is not bound to a
specific object, the recursion guard will track calls to that method globally. This means that all static functions,
including static methods on classes, will be stored within the same global context.

This means that using a static function from anywhere in your application will prevent that function from being called
everywhere else in the application until the call stack is resolved.

[See the tests](../tests/Documentation/ThroughTest.php)
```php
function through(mixed $value, callable $through): mixed
{
    return RecursionGuard\Recurser::call(fn () => $through($value), $value);
}

function pipe(mixed $value, callable ...$through): mixed
{
    return array_reduce($through, through(...), $value);
}

through('foo', 'strtoupper'); // FOO
through('FOO', 'str_split'); // ['F', 'O', 'O']
through(['F', 'O', 'O'], 'array_unique'); // ['F', 'O']
through(through(through('foo', 'strtoupper'), 'str_split'), 'array_unique');  // ['F', 'O']

pipe('foo', strtoupper(...), str_split(...), array_unique(...)); // ['F', 'O']

$unique = new class {
    public function __invoke(mixed $value)
    {
        return is_array($value)
            ? pipe($value, $this->strings(...), $this->alphanumeric(...), array_unique(...), array_values(...))
            : pipe($value, str_split(...), $this->alphanumeric(...), array_unique(...), array_values(...));
    }

    protected function strings(array $values): array
    {
        return array_filter($values, 'is_string');
    }

    protected function alphanumeric(array $values): array
    {
        return array_filter($values, 'ctype_alnum');
    }
};
$unique('foo'); // ['f', 'o']
$unique('foo bar baz'); // ['f', 'o', 'b', 'a', 'r', 'z']
$unique(['F', 'O', 'O']); // ['F', 'O']

// Calling $unique through the `through` or `pipe` function will trigger the recursion guard
through('foo', $unique); // 'foo'
pipe('foo', $unique); // 'foo'
```

### Explicitly setting the context object
In the event that you want to tie the recursion protection to a _specific_ object, no matter which object is _actually_
being called, you can pass an object to `call()` as the third parameter, or with the parameter name `for:`. This is
useful when you want to prevent recursion through related objects from the context of a different object.

[See the tests](../tests/Documentation/TravellingSalesmanTest.php)
```php
class TravellingSalesman 
{
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
```
In this example the recursion protection is attached to the `Location` object, instead of the `TravellingSalesman` which
would normally be the object getting tracked. Without setting the object to the `Location`, the recursion protection
would attach to the `TravellingSalesman` object, and stop after travelling one step.

Because the recursion guard is tied to each `Location` object, it will ensure that any other attempts to travel to a
visited location will only return a single step that is too long to be considered a valid path.

## Method signatures
The signature of each method is automatically generated by the recursion guard, based on a number of factors:
* The filename of the file that the method is defined in.
* The name of the method (if applicable).
* The class that the method is defined in (if applicable).
* The line number that the method begins on.

For example, given the following definition:
```php
<?php

namespace App\Support;

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
```
The generated signature would be something like:

`/var/www/app/Support/LinkedList.php:App\Support\LinkedList@children`

If the function is defined outside a class then the signature will be generated based on the filename and function name,
or if the function is anonymous it will use the line number that it's defined on:

```php
<?php

function once(callable $callback)
{
    // signature: /var/www/app/Support/helpers.php:once
    return RecursionGuard\Recurser::call($callback, 'RECURSION');
}

$once = function (callable $callback) use (&$once) {
    // signature: /var/www/app/Support/helpers.php:9
    return RecursionGuard\Recurser::call(fn () => $once($callback), 'RECURSION');
};
```

### Supplying a custom signature
You can define your own custom signature by passing a string as the fourth argument to the `call` method, or with the
named attribute `as:`. This can be useful if you want fine-grained control to allow limited recursion based on
parameters passed to the method.

[See the tests](../tests/Documentation/FibTest.php)
```php
function fib(int $position): int
{
    return RecursionGuard\Recurser::call(
        // Add the two previous numbers together
        fn () => fib($position - 1) + fib($position - 2),
        // Return 0 for negative positions, and 1 for position 0
        max(0, ($position ?: 1)),
        // Allow recursion until we hit position 0
        as: sprintf('fib(%d)', max(0, ($position ?: 1))),
    );
}

fib(0);   // 0
fib(1);   // 1
fib(2);   // 1
fib(3);   // 2
fib(10);  // 55
fib(-50); // 0
```
