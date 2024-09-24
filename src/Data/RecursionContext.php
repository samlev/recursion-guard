<?php

declare(strict_types=1);

namespace RecursionGuard\Data;

use ArrayAccess;
use Closure;
use JsonSerializable;
use RecursionGuard\Exception\InvalidContextException;
use RecursionGuard\Support\ArrayWritesForbidden;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;

/**
 * @phpstan-import-type TraceArray from Trace
 * @phpstan-import-type FrameArray from Frame
 *
 * @implements ArrayAccess<'line'|'class'|'function'|'file'|'object'|'signature', int|string|object|null>
 */
readonly class RecursionContext implements ArrayAccess, JsonSerializable
{
    use ArrayWritesForbidden;

    public function __construct(
        public string $file = '',
        public string $class = '',
        public string $function = '',
        public int $line = 0,
        public object|null $object = null,
    ) {
        //
    }

    public function signature(): string
    {
        return sprintf(
            '%s:%s%s',
            $this->file,
            $this->class ? ($this->class . '@') : '',
            $this->function ?: $this->line,
        );
    }

    /**
     * @param Trace|TraceArray $trace
     */
    public static function fromTrace(Trace|array $trace): self
    {
        $trace = Trace::make($trace)->frames();

        if (empty($trace)) {
            throw InvalidContextException::make($trace);
        }

        return new self(
            $trace[0]->file ?: '',
            $trace[1]?->class ?? '',
            $trace[1]?->function ?? '',
            $trace[0]->line,
            $trace[1]?->object ?? null,
        );
    }

    /**
     * @param callable(): mixed $callable
     * @return self
     * @throws ReflectionException
     */
    public static function fromCallable(callable $callable): self
    {
        return (match (true) {
            is_string($callable), $callable instanceof Closure => function (string|Closure $callable) {
                $reflector = new ReflectionFunction($callable);
                return new self(
                    $reflector->getFileName() ?: '',
                    $reflector->getClosureScopeClass()?->getName() ?? '',
                    $reflector->getName() === '{closure}'
                        ? str_replace("\n", ' ', (string)$reflector)
                        : $reflector->getName(),
                    $reflector->getStartLine() ?: 0,
                    $reflector->getClosureThis(),
                );
            },
            is_object($callable) && method_exists($callable, '__invoke') => function (object $callable) {
                $reflector = new ReflectionClass($callable);
                return new self(
                    $reflector->getFileName() ?: '',
                    $reflector->getName(),
                    '__invoke',
                    $reflector->getStartLine() ?: 0,
                    $callable,
                );
            },
            default => fn (callable $callable) => throw InvalidContextException::make($callable),
        })(
            $callable
        );
    }

    /**
     * @param callable|array<int, FrameArray>|TraceArray|Trace $from
     * @return RecursionContext
     * @throws ReflectionException
     */
    public static function make(array|callable|Trace $from): self
    {
        return is_callable($from)
            ? static::fromCallable($from)
            : static::fromTrace($from);
    }

    /**
     * @param int|string $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return match ($offset) {
            'file', 'line', 'class', 'function', 'signature' => true,
            'object' => $this->$offset !== null,
            default => false,
        };
    }

    /**
     * @param int|string $offset
     * @return ($offset is 'line' ? int : ($offset is 'object' ? object|null : string|null))
     */
    public function offsetGet(mixed $offset): mixed
    {
        return match ($offset) {
            'file', 'line', 'class', 'function', 'object' => $this->$offset,
            'signature' => $this->signature(),
            default => null,
        };
    }

    /**
     * @return array{file: string, class: string, function: string, line: int, object: object|null, signature: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'file' => $this->file,
            'class' => $this->class,
            'function' => $this->function,
            'line' => $this->line,
            'object' => $this->object,
            'signature' => $this->signature(),
        ];
    }
}
