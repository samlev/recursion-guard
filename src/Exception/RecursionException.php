<?php

declare(strict_types=1);

namespace RecursionGuard\Exception;

use RecursionGuard\Recursable;
use Throwable;

final class RecursionException extends \RuntimeException
{
    public const MESSAGE_OVERFLOW = 'Call stack for [%s] has overflowed.';
    public const MESSAGE_FINISHED = 'Call stack for [%s] has completed.';
    public const MESSAGE_RECURSING = 'Callback for [%s] has been called while resolving return value.';
    public const MESSAGE_STARTED = 'Callback for [%s] has been called recursively.';
    public const MESSAGE_DEFAULT = 'Call stack for [%s] has not commenced.';

    /** @var Recursable<mixed>|null */
    protected ?Recursable $recursable = null;

    /**
     * @param Recursable<mixed> $recursable
     * @return string
     */
    public static function makeMessage(Recursable $recursable): string
    {
        return sprintf(
            match (true) {
                $recursable->overflown() => self::MESSAGE_OVERFLOW,
                $recursable->finished() => self::MESSAGE_FINISHED,
                $recursable->recursing() => self::MESSAGE_RECURSING,
                $recursable->started() => self::MESSAGE_STARTED,
                default => self::MESSAGE_DEFAULT,
            },
            $recursable->signature(),
        );
    }


    /**
     * @param Recursable<mixed> $recursable
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
     * @param Recursable<mixed> $recursable
     * @return $this
     */
    public function withRecursable(Recursable $recursable): self
    {
        $this->recursable ??= $recursable;

        return $this;
    }

    /**
     * @return Recursable<mixed>|null
     */
    public function getRecursable(): ?Recursable
    {
        return $this->recursable;
    }
}
