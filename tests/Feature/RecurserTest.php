<?php

declare(strict_types=1);

use RecursionGuard\Factory;
use RecursionGuard\Data\Trace;
use RecursionGuard\Recursable;
use RecursionGuard\Recurser;
use Tests\Support\SetsState;

covers(Recurser::class, Recursable::class);

beforeEach(function () {
    Recurser::flush();
});

it('manages instances', function () {
    $instance = Recurser::instance();

    expect($instance)->toBeInstanceOf(Recurser::class)
        ->and(Recurser::instance())->toBe($instance);

    Recurser::flush();

    expect(Recurser::instance())->toBeInstanceOf(Recurser::class)
        ->not->toBe($instance);
});

it('manages recursables', function () {
    $callable = new class () {
        public function __invoke(): void
        {
            //
        }
    };

    $factory = new Factory();

    $instance = new class ($factory) extends Recurser {
        public function add(Recursable $recursable): Recurser
        {
            $this->setValue($recursable);

            return $this;
        }
    };

    $one = $factory->makeRecursable($callable);
    $two = $factory->makeRecursable($callable);


    expect($instance->find($one))->toBeNull()
        ->and($instance->find($two))->toBeNull();

    $instance->add($one);

    expect($instance->find($one))->toBe($one)
        ->and($instance->find($two))->toBe($one)
        ->not->toBe($two);

    $instance->release($one);

    expect($instance->find($one))->toBeNull()
        ->and($instance->find($two))->toBeNull();
});

it('executes callable and returns result', function () {
    $executed = false;
    $callable = function () use (&$executed) {
        $executed = true;
        return 'result-value';
    };

    $instance = new Recurser();
    $recursable = $instance->factory->makeRecursable($callable);
    $output = $instance->guard($recursable);

    expect($output)->toBe('result-value')
        ->and($executed)->toBeTrue();
});

it('returns onRecursion handler when recursable already in stack', function () {
    $recursable = (new class (fn () => 'original-value', 'on-recursion-value') extends Recursable {
        use SetsState;
    })->state(
        started: true,
        stackDepth: 1,
    );

    $instance = new class () extends Recurser {
        public function add(Recursable $recursable): Recurser
        {
            $this->setValue($recursable);

            return $this;
        }
    };

    $output = $instance->add($recursable)->guard($recursable);

    expect($output)->toBe('on-recursion-value');
});

it('releases recursable in finally block', function () {
    $factory = new Factory();
    $instance = new class ($factory) extends Recurser {
        public function defaultStack(): array
        {
            return $this->getStack($this->defaultScope);
        }
    };

    $callable = fn () => 'result';
    $recursable = $factory->makeRecursable($callable);

    $instance->guard($recursable);

    expect($instance->defaultStack())->not->toHaveKey($recursable->hash());
});

it('releases recursable in finally block on exception', function () {
    $factory = new Factory();
    $instance = new class ($factory) extends Recurser {
        public function defaultStack(): array
        {
            return $this->getStack($this->defaultScope);
        }
    };

    $recursable = $factory->makeRecursable(function () {
        throw new \Exception('test error');
    });

    expect(fn () => $instance->guard($recursable))->toThrow(Exception::class, 'test error')
        ->and($instance->defaultStack())->not->toHaveKey($recursable->hash());
});

it('returns early when recursable is found in stack', function () {
    $instance = new class () extends Recurser {
        public function add(Recursable $recursable): Recurser
        {
            $this->setValue($recursable);

            return $this;
        }
    };

    $callCount = 0;
    $callback = function () use (&$callCount) {
        $callCount++;
        return 'value-' . $callCount;
    };

    $recursable = (new class ($callback, 'recursion-response') extends Recursable {
        use SetsState;
    })->state(
        started: true,
        stackDepth: 1,
    );

    $instance->add($recursable);
    $output = $instance->guard($recursable);

    expect($callCount)->toBe(0)
        ->and($output)->toBe('recursion-response');
});

it('uses the matching call from the active stack before re-inserting a new target', function () {
    $instance = new class () extends Recurser {
        public function add(Recursable $recursable): Recurser
        {
            $this->setValue($recursable);

            return $this;
        }
    };

    $recursive = new class (fn () => 'original', 'original-response', 'same-signature') extends Recursable {
        use SetsState;
    };
    $replacement = new class (fn () => 'replacement', 'replacement-response', 'same-signature') extends Recursable {
        use SetsState;
    };

    $instance->add($recursive);
    $output = $instance->guard($replacement);

    expect($output)->toBe('original')
        ->and($instance->find($recursive))->toBe($recursive)
        ->and($instance->find($replacement))->toBe($recursive);
});

it('tracks object-scoped recursion stacks rather than default scope', function () {
    $instance = new class () extends Recurser {
        public function add(Recursable $recursable): Recurser
        {
            $this->setValue($recursable);

            return $this;
        }

        public function clear(Recursable $recursable): Recurser
        {
            $this->release($recursable);

            return $this;
        }

        public function stackFor(object $instance): array
        {
            return $this->getStack($instance);
        }
    };

    $default = $instance->factory->makeRecursable(static fn () => 'default');
    $scope = new stdClass();
    $target = $instance->factory->makeRecursable(static fn () => 'object');
    $target->forObject($scope);

    $instance->add($default);
    $instance->add($target);

    expect($instance->stackFor($instance->defaultScope))->toHaveKey($default->hash())
        ->and($instance->stackFor($instance->defaultScope))->not->toHaveKey($target->hash())
        ->and($instance->stackFor($scope))->toHaveKey($target->hash())
        ->and($instance->stackFor($scope))->not->toHaveKey($default->hash());

    $instance->clear($default);
    $instance->clear($target);

    expect($instance->stackFor($instance->defaultScope))->not->toHaveKey($default->hash())
        ->and($instance->stackFor($instance->defaultScope))->not->toHaveKey($target->hash())
        ->and($instance->stackFor($scope))->not->toHaveKey($target->hash())
        ->and($instance->stackFor($scope))->not->toHaveKey($default->hash());
});


it('releases only the matching object-scoped recursable and keeps siblings intact', function () {
    $instance = new class () extends Recurser {
        public function add(Recursable $recursable): Recurser
        {
            $this->setValue($recursable);

            return $this;
        }

        public function clear(Recursable $recursable): Recurser
        {
            $this->release($recursable);

            return $this;
        }

        public function stackFor(object $instance): array
        {
            return $this->getStack($instance);
        }
    };

    $scope = new stdClass();
    $first = $instance->factory->makeRecursable(static fn () => 'first', null, null, 'first-signature');
    $second = $instance->factory->makeRecursable(static fn () => 'second', null, null, 'second-signature');

    $first->forObject($scope);
    $second->forObject($scope);

    $instance->add($first);
    $instance->add($second);

    expect($instance->stackFor($scope))
        ->toHaveKey($first->hash())
        ->and($instance->stackFor($scope))->toHaveKey($second->hash());

    $instance->clear($first);

    expect($instance->stackFor($scope))
        ->not->toHaveKey($first->hash())
        ->and($instance->stackFor($scope))->toHaveKey($second->hash());
});

it('releases a finished recursable from the active stack after guard resolves', function () {
    $instance = new Recurser();
    $recursable = $instance->factory->makeRecursable(fn () => 'result');

    expect($instance->guard($recursable))->toBe('result')
        ->and($instance->find($recursable))->toBeNull();
});

it('derives default signatures from a two-frame backtrace in static call', function () {
    $factory = new class () extends Factory {
        public string $captured_signature = '';
        public int $captured_backtrace_depth = 0;

        public function makeRecursable(
            callable $callback,
            mixed $onRecursion = null,
            ?object $object = null,
            ?string $signature = null,
            Trace|array $backTrace = [],
        ): Recursable {
            $this->captured_backtrace_depth = count($backTrace);
            $this->captured_signature = $this->makeContext($callback, $backTrace)->signature;

            return parent::makeRecursable($callback, $onRecursion, $object, $signature, $backTrace);
        }
    };

    $setter = new class () extends Recurser {
        public static function install(Recurser $instance): void
        {
            Recurser::$instance = $instance;
        }
    };

    $setter::install(new Recurser($factory));

    $caller = new class () {
        public function invoke(): string
        {
            return Recurser::call(fn () => 'ok');
        }
    };

    expect($caller->invoke())->toBe('ok')
        ->and($factory->captured_backtrace_depth)->toBe(2)
        ->and($factory->captured_signature)->toContain(sprintf(':%s@invoke', get_class($caller)));
});

it('binds recursables to default scope when no object is provided', function () {
    $instance = new Recurser();
    $recursable = $instance->factory->makeRecursable(static fn () => 'ok');

    expect($recursable->object())->toBeNull();

    $instance->guard($recursable);

    expect($recursable->object())->toBe($instance->defaultScope);
});
