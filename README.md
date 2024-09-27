# Recursion Guard
A simple, zero dependency mechanism for preventing infinite recursion in PHP.

<p align="center">
<a href="https://github.com/samlev/recursion-guard/actions"><img src="https://github.com/samlev/recursion-guard/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/samlev/recursion-guard"><img src="https://img.shields.io/packagist/dt/samlev/recursion-guard" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/samlev/recursion-guard"><img src="https://img.shields.io/packagist/v/samlev/recursion-guard" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/samlev/recursion-guard"><img src="https://img.shields.io/packagist/l/samlev/recursion-guard" alt="License"></a>
</p>

## Installation
```bash
composer require samlev/recursion-guard
```

## Usage
You can prevent a function from being called recursively by using `RecursionGuard\Recurser::call()` to execute a
callback and provide a default result if the function is called recursively within the callback.
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

See [the documentation](docs/index.md) for more explanation, examples, and advanced usage.

