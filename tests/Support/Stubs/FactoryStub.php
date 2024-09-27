<?php

declare(strict_types=1);

namespace Tests\Support\Stubs;

use Closure;
use RecursionGuard\Data\Frame;
use RecursionGuard\Data\RecursionContext;
use RecursionGuard\Data\Trace;
use RecursionGuard\Factory;
use RecursionGuard\Recursable;

class FactoryStub extends Factory
{
    private $observer;

    public function attach($observer)
    {
        $this->observer = $observer;
    }

    public function makeRecursable(
        callable $callback,
        mixed $onRecursion = null,
        ?object $object = null,
        ?string $signature = null,
        Trace|array $backTrace = [],
    ): Recursable {
        $this->observer->makeRecursable(
            $callback,
            $onRecursion,
            $object,
            $signature,
            $backTrace,
        );

        return parent::makeRecursable(
            $callback,
            $onRecursion,
            $object,
            $signature,
            $backTrace,
        );
    }
    public function makeContext(callable $callback, Trace|array $backTrace = []): RecursionContext
    {
        $this->observer->makeContext($callback, $backTrace);

        return parent::makeContext($callback, $backTrace);
    }
    public function makeContextFromTrace(Trace $trace): RecursionContext
    {
        $this->observer->makeContextFromTrace($trace);

        return parent::makeContextFromTrace($trace);
    }
    public function makeContextFromCallable(callable $callable): RecursionContext
    {
        $this->observer->makeContextFromCallable($callable);

        return parent::makeContextFromCallable($callable);
    }

    public function makeContextFromFunction(Closure|string $callable): RecursionContext
    {
        $this->observer->makeContextFromFunction($callable);

        return parent::makeContextFromFunction($callable);
    }
    public function makeContextFromObject(object $callable): RecursionContext
    {
        $this->observer->makeContextFromObject($callable);

        return parent::makeContextFromObject($callable);
    }

    public function makeContextFromCallableArray(array $callable): RecursionContext
    {
        $this->observer->makeContextFromCallableArray($callable);

        return parent::makeContextFromCallableArray($callable);
    }

    public function makeTrace(array|Trace $trace): Trace
    {
        $this->observer->makeTrace($trace);

        return parent::makeTrace($trace);
    }

    public function makeFrame(array|Frame $frame): Frame
    {
        $this->observer->makeFrame($frame);

        return parent::makeFrame($frame);
    }
}
