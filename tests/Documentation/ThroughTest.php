<?php

declare(strict_types=1);

use function Tests\Support\Documentation\Through\through;
use function Tests\Support\Documentation\Through\pipe;

it('passes a value through a callback', function () {
    expect(through('foo', 'strtoupper'))->toBe('FOO');
});

it('pipes a value through multiple callbacks', function () {
    expect(pipe('foo', strtoupper(...), str_split(...), array_unique(...)))
        ->toBe(['F', 'O']);
});

it('does not recurse if nesting through calls', function () {
    expect(through(through(through('foo', 'strtoupper'), 'str_split'), 'array_unique'))
        ->toBe(['F', 'O']);
});

it('returns default if piping through through', function () {
    $uppercase = fn (string $value) => through($value, strtoupper(...));
    $split = fn (string $value) => through($value, str_split(...));
    $unique = fn (array|string $value) => through(is_string($value) ? $split($value) : $value, array_unique(...));

    expect($uppercase('foo'))->toBe('FOO')
        ->and($split('foo'))->toBe(['f', 'o', 'o'])
        ->and($unique('foo'))->toBe(['f', 'o'])
        ->and($unique($uppercase('foo')))->toBe(['F', 'O'])
        ->and(pipe('foo', $uppercase, $split, $unique))->toBe('foo');
});

it('returns default if nesting pipe calls', function () {
    $unique = fn (string $value) => pipe(
        $value,
        str_split(...),
        array_unique(...),
        fn ($v) => array_map('trim', $v),
        array_filter(...),
        implode(...),
    );

    expect($unique('foo'))->toBe('fo')
        ->and($unique('foo bar baz'))->toBe('fobarz')
        ->and($unique('FOO'))->toBe('FO')
        ->and($unique(pipe('foo bar baz', strtoupper(...), strrev(...))))->toBe('ZABROF')
        ->and(through('foo', $unique))->toBe('foo')
        ->and(pipe('foo bar baz', strtoupper(...), $unique, strrev(...)))->toBe('ZAB RAB OOF');
});

it('returns default if nesting pipe calls via object', function () {
    $unique = new class () {
        public function __invoke(mixed $value)
        {
            return is_array($value)
                ? pipe($value, $this->strings(...), $this->alphanumeric(...), array_unique(...), array_values(...))
                : pipe($value, str_split(...), $this->alphanumeric(...), array_unique(...), array_values(...));
        }

        protected function strings(array $values): array
        {
            return array_filter($values, 'is_string');
        }

        protected function alphanumeric(array $values): array
        {
            return array_filter($values, 'ctype_alnum');
        }
    };

    expect($unique('foo'))->toBe(['f', 'o'])
        ->and($unique('foo bar baz'))->toBe(['f', 'o', 'b', 'a', 'r', 'z'])
        ->and($unique(['F', 'O', 'O']))->toBe(['F', 'O'])
        ->and(through('foo', $unique))->toBe('foo')
        ->and(pipe('foo', strtoupper(...), $unique))->toBe('FOO');
});
