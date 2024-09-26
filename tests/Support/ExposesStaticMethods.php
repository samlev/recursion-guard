<?php

declare(strict_types=1);

namespace Tests\Support;

trait ExposesStaticMethods
{
    public static function __callStatic(string $method, array $parameters)
    {
        $method = str_starts_with($method, 'expose_')
            ? substr($method, 7)
            : $method;

        return method_exists(static::class, $method)
            ? static::$method(...$parameters)
            : throw new \BadMethodCallException(sprintf('Static Method %s::%s does not exist.', static::class, $method));
    }
}
