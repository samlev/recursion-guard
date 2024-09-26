# Use within global functions

## Single function
Here is a simple example of how to use the recursion guard with a global function. The `bozo_repeat` function will call
itself recursively, but only the first call will actually execute the callback. The second and subsequent calls will
return the default value instead.

```php
function bozo_repeat(string $repeat = ''): string
{
    return RecursionGuard\Recurser::call(
        // The callback that we want to call
        fn () => bozo_repeat() . ' : ' . bozo_repeat(),
        // What to return if this function is called recursively
        $repeat ?: 'bozo(' . random_int(0, 100)) . ')';
    );
}

bozo_repeat(); // 'bozo(4) : bozo(4)'
bozo_repeat('foo'); // 'foo : foo'
bozo_repeat(); // 'bozo(88) : bozo(88)'
```
Notice that the default value is also only evaluated once. When the callback returns, the call stack is released, and
the function can be called again for new values.

To help visualize the call stack, here is a breakdown of the calls:
```php
bozo_repeat():
    $recurseWith = 'bozo(' . random_int(0, 100)) . ')'; // 'bozo(4)'
    $callback(): 
        bozo_repeat() // 'bozo(4)'
        bozo_repeat() // 'bozo(4)'
// 'bozo(4) : bozo(4)'

bozo_repeat('foo'):
    $recurseWith = 'foo';
    $callback: 
        bozo_repeat() // 'foo'
        bozo_repeat() // 'foo'
// 'foo : foo'

bozo_repeat():
    $recurseWith = 'bozo(' . random_int(0, 100)) . ')'; // 'bozo(88)'
    $callback(): 
        bozo_repeat() // 'bozo(88)'
        bozo_repeat() // 'bozo(88)'
// 'bozo(88) : bozo(88)'
```
As you can see, the default value is evaluated before the callback is called, so that there is a value to return if the
callback calls the parent function again.

## Stacked functions

The protection from the recursion guard is tied to the call stack, and will be released after the top level call is
finally resolved. This means that if you call a method which calls the original function without calling the original
function directly, the recursion guard will still work as expected.

```php
function once(callable $callback)
{
    return RecursionGuard\Recurser::call($callback, 'RECURSION');
}

function twice(callable $callback)
{
    return once($callback) . ', ' . once($callback);
}

function roll_dice()
{
    return random_int(1, 6);
}

function roll_two_dice()
{
    return twice('roll_dice');
}

once('roll_dice'); // 3
twice('roll_dice'); // 2, 6
roll_two_dice(); // 4, 1
once('roll_two_dice'); // RECURSION, RECURSION
```
Notice that because `twice()` uses `once()`, the recursion guard is in effect and returns the top level default value
when it is called inside the call stack. To visualize it more clearly, The call stack goes like this:
```php
once('roll_dice'):
    $recurseWith = 'RECURSION';
    roll_dice():
        random_int(1, 6) // 3

twice('roll_dice'):
    once('roll_dice'):
        $recurseWith = 'RECURSION';
        roll_dice():
            random_int(1, 6) // 2
    once('roll_dice'):
        $recurseWith = 'RECURSION';
        roll_dice():
            random_int(1, 6) // 6

roll_two_dice():
    twice('roll_dice'):
        once('roll_dice'):
            $recurseWith = 'RECURSION';
            roll_dice():
                random_int(1, 6) // 4
        once('roll_dice'):
            $recurseWith = 'RECURSION';
            roll_dice():
                random_int(1, 6) // 1

once('roll_two_dice'):
    $recurseWith = 'RECURSION';
    roll_two_dice():
        twice('roll_dice'):
            once('roll_dice') // 'RECURSION'
            once('roll_dice') // 'RECURSION'
```
