<?php

declare(strict_types=1);

namespace Tests\Support;

trait SetsState
{
    public function state(...$params): static
    {
        foreach ($params as $prop => $value) {
            $this->{$prop} = $value;
        }

        return $this;
    }
}
