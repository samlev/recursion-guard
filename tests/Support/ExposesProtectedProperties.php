<?php

declare(strict_types=1);

namespace Tests\Support;

trait ExposesProtectedProperties
{
    public function __get(string $property): mixed
    {
        $property = str_starts_with($property, 'expose_')
            ? substr($property, 7)
            : $property;

        return property_exists($this, $property)
            ? ($this->{$property} ?? null)
            : null;
    }
    public function __set(string $property, mixed $value): void
    {
        $property = str_starts_with($property, 'expose_')
            ? substr($property, 7)
            : $property;

        $this->{$property} = $value;
    }
}
