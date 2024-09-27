<?php

declare(strict_types=1);

namespace Tests\Support;

trait ExposesProtectedMethods
{
    public function __call(string $method, array $parameters): mixed
    {
        $method = str_starts_with($method, 'expose_')
            ? substr($method, 7)
            : $method;

        return method_exists($this, $method)
            ? $this->$method(...$parameters)
            : throw new \BadMethodCallException(sprintf('Method %s::%s does not exist.', $this::class, $method));
    }
}
