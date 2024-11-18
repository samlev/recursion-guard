# Recursion Guard
A simple, zero dependency mechanism for preventing infinite recursion in PHP.

<p align="center">
<a href="https://github.com/samlev/recursion-guard/actions"><img src="https://github.com/samlev/recursion-guard/actions/workflows/tests.yml/badge.svg" alt="Build Status"></a>
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

## Testing
You can run individual test suites using composer commands:
```bash
# Static Analysis with phpstan
composer test:types

# Architecture tests
composer test:architecture
# Documentation tests (tests that cover any code in the documentation)
composer test:docs
# Feature/integration tests
composer test:feature
# Unit tests
composer test:unit

# Code coverage
composer test:coverage

# Mutation tests
composer test:mutate
```

Or you can run grouped tests:
```bash
# All code style checks
composer lint

# Static analysis and main test suites
composer test

# All checks that should be performed before a PR (linting, static analysis, and mutation tests)
composer test:pr
```

### Code Style

The code style for this project adheres to PSR-12, and can be checked using the following
composer commands:
```bash
# Code Style checks with phpcs
composer lint:phpcs
# Code Style checks with PHP-CS-Fixer
composer lint:phpcsfixer

# Run all code style checks
composer lint
```

You can attempt to automatically fix a number of code style issues with the  command:
```bash
composer lint:fix
```

## Contributing
Contributions via PR are welcome, either as complete changes (including tests), or as
failing test cases.

