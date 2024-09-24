<?php

declare(strict_types=1);

namespace Tests\Support;

use Tests\TestCase;

class StubSpy
{
    protected static ?StubSpy $instance = null;

    protected array $called = [];
    protected bool $checked = false;
    protected bool $used = false;

    public function __construct(
        protected ?TestCase $test = null,
        protected array $expected = [],
    ) {
        //
    }

    public static function make(TestCase $test, array $expected = []): self
    {
        return static::$instance = new self($test, $expected);
    }

    public static function flush(): void
    {
        if (static::$instance) {
            static::$instance->assert();
            unset(static::$instance->test->spy);
        }

        static::$instance = null;
    }

    public static function instance(): ?self
    {
        return static::$instance;
    }

    public function call(string $method, array $params): void
    {
        if ($this->checked) {
            $this->test->fail(sprintf(
                'Method [%s] was called after checking without new expectations',
                $method,
            ));
        }
        $this->used = true;

        $this->called[$method] ??= [];
        $this->called[$method][] = $params;
    }

    public function expect(string $method, array $params): self
    {
        $this->used = true;
        $this->checked = false;

        $this->expected[$method] ??= [];
        $this->expected[$method][] = $params;

        return $this;
    }

    public function assert(): void
    {
        if (!$this->used) {
            return;
        }
        $this->checked = true;

        $expected = $this->expected;
        $called = $this->called;

        foreach ($expected as $method => $calls) {
            $this->test->assertArrayHasKey($method, $this->called, sprintf(
                'Expected method [%s] was not called.',
                $method,
            ));

            $called = $called[$method];

            do {
                $params = array_shift($calls);

                foreach ($called as $i => $calledParams) {
                    if ($params === $calledParams) {
                        unset($called[$i]);
                        continue 2;
                    }
                }

                $this->test->fail(sprintf(
                    'Method [%s] was not called with expected parameters %s',
                    $method,
                    json_encode($params),
                ));
            } while ($calls);

            $this->test->assertSame([], $called, sprintf(
                'Method [%s] was called with %d time(s) with unexpected parameters %s',
                $method,
                count($called),
                json_encode($called),
            ));

            unset($called[$method]);
        }

        $this->test->assertSame([], $called, sprintf(
            'Unexpected method(s) were called: %s',
            implode(', ', array_keys($called)),
        ));
    }

    public function __destruct()
    {
        if (!$this->checked) {
            $this->assert();
        }
    }
}
