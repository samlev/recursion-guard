<?php

declare(strict_types=1);

namespace RecursionGuard\Support;

use RecursionGuard\Recursable;
use Throwable;

/**
 * @template TReturnType
 */
trait WithRecursable
{
    /**
     * @var Recursable<TReturnType>|null $recursable
     */
    protected ?Recursable $recursable = null;

    /**
     * @param Recursable<TReturnType> $recursable
     * @return string
     */
    abstract protected static function makeMessage(Recursable $recursable): string;

    /**
     * @param Recursable<TReturnType> $recursable
     * @param string|null $message
     * @param int $code
     * @param Throwable|null $previous
     * @return self
     */
    public static function make(
        Recursable $recursable,
        ?string $message = null,
        int $code = 0,
        ?Throwable $previous = null
    ): self {
        return (new self(
            $message ?: static::makeMessage($recursable),
            $code,
            $previous
        ))->withRecursable($recursable);
    }

    /**
     * @param Recursable<TReturnType> $recursable
     * @return $this
     */
    protected function withRecursable(Recursable $recursable): static
    {
        $this->recursable ??= $recursable;

        return $this;
    }

    /**
     * @return Recursable<TReturnType>|null
     */
    public function getRecursable(): ?Recursable
    {
        return $this->recursable;
    }
}
