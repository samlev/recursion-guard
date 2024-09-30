<?php

declare(strict_types=1);

namespace RecursionGuard\Data;

/**
 * @extends BaseData<'line'|'class'|'function'|'file'|'object'|'signature', int|string|object|null>
 */
readonly class RecursionContext extends BaseData
{
    public string $signature;

    final public function __construct(
        public string $file = '',
        public string $class = '',
        public string $function = '',
        public int $line = 0,
        public object|null $object = null,
        string $signature = '',
    ) {
        $this->signature = $signature ?: $this->signature();
    }

    protected function signature(): string
    {
        return sprintf(
            '%s:%s%s',
            $this->file,
            $this->class ? ($this->class . '@') : '',
            $this->function ?: $this->line,
        );
    }
}
