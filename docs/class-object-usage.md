# Use within classes/objects

When you use the recursion guard within the context of an instantiated object, the protection against recursion is
linked exclusively to the _actual instance_ of the object that a method is called on. This means that you can call the
same method on different objects of the same class within the same call stack, and each one will have its own recursion
guard.

[See the tests](../tests/Documentation/RepeaterTest.php)

```php
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
        return RecursionGuard\Recurser::call(
            fn () => sprintf('%d: [%s]', $this->times, implode(', ', array_fill(0, $this->times, (is_callable($repeat) ? $repeat() : $repeat)))),
            sprintf('%d (default): [%s]', $this->times, implode(', ', array_fill(0, $this->times, $default))),
        );
    }
}

$once = new Repeater(1);
$once('foo', 'bar'); // '1: [foo]'
$once(fn () => $once('foo', 'bar'), 'baz'); // '1: [1 (default): [baz]]'

$twice = new Repeater(2);
$twice('foo', 'bar'); // '2: [foo, foo]'
$twice(fn () => $once('foo', 'bar'), 'baz'); // '2: [1: [foo], 1: [foo]]'
$twice(fn () => $twice('foo', 'bar'), 'baz'); // '2: [2 (default): [baz, baz], 2 (default): [baz, baz]]'

$repeatFooOnce = fn () => $once('foo', 'bar');
$repeatFooOnce(); // '1: [foo]'
$once($repeatFooOnce, 'baz'); // '1: [1 (default): [baz]]'
$twice($repeatFooOnce, 'baz'); // '2: [1: [foo], 1: [foo]]'

$repeatFooTwice = fn () => $twice('foo', 'bar');
$repeatFooTwice(); // '2: [foo, foo]'
$once($repeatFooTwice, 'baz'); // '1: [2: [foo, foo]]'
$twice($repeatFooTwice, 'baz'); // '2: [2 (default): [baz, baz], 2 (default): [baz, baz]]'
```
Despite all being instance of the same class, the "default" value is only returned when a call is made recursively on
the same _instance_, even if that call is happening through other functions.

See [understanding recursion context](understanding-recursion-context.md) for more information on how the recursion
guard works within the context of an object, and how to override an object if required.
