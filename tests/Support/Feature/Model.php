<?php

declare(strict_types=1);

namespace Tests\Support\Feature;

use RecursionGuard\Recurser;

class Model
{
    protected array $attributes = [];

    protected array $relations = [];

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * @param Model|Model[] $value
     * @return $this
     */
    public function setRelation(string $name, Model|array $value): self
    {
        $this->relations[$name] = $value;

        return $this;
    }

    public function __set(string $property, mixed $value): void
    {
        $this->attributes[$property] = $value;
    }

    public function __get(string $property): mixed
    {
        return $this->attributes[$property] ?? $this->relations[$property] ?? null;
    }

    /**
     * @param Model|null $context
     * @return array
     */
    public function toArray(?Model $context = null, bool $sign = false): array
    {
        return Recurser::call(
            fn () => [
                ...$this->attributes,
                ...array_map(
                    fn (Model|array $related) =>
                    $related instanceof Model
                        ? $related->toArray($context, $sign)
                        : array_map(fn (Model $each) => $each->toArray($context, $sign), $related),
                    $this->relations
                ),
            ],
            $this->attributes,
            for: $context,
            as: $sign && $context
                ? sprintf('Model<%s>::toArray()', spl_object_id($this))
                : null
        );
    }
}
