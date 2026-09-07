<?php

declare(strict_types=1);

use RecursionGuard\Helper\Obj;
use Tests\Support\Data\NullableProperties;
use Tests\Support\Data\NullablePropertiesWithDefaults;
use Tests\Support\Data\PrivatePropertiesWithDefaults;
use Tests\Support\Data\PromotedMixedProperties;
use Tests\Support\Data\PromotedProperties;
use Tests\Support\Data\ProtectedPropertiesWithDefaults;
use Tests\Support\Data\PublicPropertiesWithDefaults;
use Tests\Support\Data\PublicPropertiesWithoutDefaults;
use Tests\Support\Data\ReadonlyClass;
use Tests\Support\Data\ReadonlyNullableClass;
use Tests\Support\Data\ReadonlyPromotedProperties;
use Tests\Support\Data\StaticProperties;

covers(Obj::class);

it('returns default values for class properties from class string', function (object $class, array $expected) {
    expect(Obj::defaults(get_class($class)))->toEqual($expected);
})->with('classes');

it('returns default values for class properties from object', function (object $class, array $expected) {
    expect(Obj::defaults($class))->toEqual($expected);
})->with('classes');

dataset('classes', [
    'standard class' => [
        new stdClass(),
        [],
    ],
    'class without defaults' => [
        new PublicPropertiesWithoutDefaults(),
        [],
    ],
    'class with defaults' => [
        new PublicPropertiesWithDefaults(),
        ['null' => null, 'string' => 'foo', 'int' => 42, 'bool' => true, 'array' => []],
    ],
    'nullable properties' => [
        new NullableProperties(),
        ['string' => null, 'int' => null, 'bool' => null, 'array' => null, 'object' => null],
    ],
    'nullable properties with defaults' => [
        new NullablePropertiesWithDefaults(),
        ['string' => 'foo', 'int' => 42, 'bool' => true, 'array' => []],
    ],
    'protected properties' => [
        new ProtectedPropertiesWithDefaults(),
        ['null' => null, 'string' => 'foo', 'int' => 42, 'bool' => true, 'array' => []],
    ],
    'private properties' => [
        new PrivatePropertiesWithDefaults(),
        ['null' => null, 'string' => 'foo', 'int' => 42, 'bool' => true, 'array' => []],
    ],
    'static properties' => [
        new StaticProperties(),
        [],
    ],
    'readonly class' => [
        new ReadonlyClass(),
        ['null' => null],
    ],
    'readonly nullable class' => [
        new ReadonlyNullableClass(),
        ['null' => null, 'string' => null, 'int' => null, 'bool' => null, 'array' => null],
    ],
    'extended public properties' => [
        new class () extends PublicPropertiesWithDefaults {
        },
        ['null' => null, 'string' => 'foo', 'int' => 42, 'bool' => true, 'array' => []],
    ],
    'extended protected properties' => [
        new class () extends ProtectedPropertiesWithDefaults {
        },
        ['null' => null, 'string' => 'foo', 'int' => 42, 'bool' => true, 'array' => []],
    ],
    'extended private properties' => [
        new class () extends PrivatePropertiesWithDefaults {
        },
        [],
    ],
    'extended properties with overrides' => [
        new class () extends PublicPropertiesWithDefaults {
            public string $string = 'bar';
            public int $int = 99;
            public bool $bool = false;
            public array $array = ['foo' => 'bar'];
        },
        ['null' => null, 'string' => 'bar', 'int' => 99, 'bool' => false, 'array' => ['foo' => 'bar']],
    ],
    'constructor promoted properties' => [
        new PromotedProperties(int: 99, bool: false, array: ['foo' => 'bar']),
        ['null' => null, 'string' => 'foo', 'int' => 42, 'bool' => true, 'array' => [], 'object' => new stdClass()],
    ],
    'readonly constructor promoted properties' => [
        new ReadonlyPromotedProperties('foo', int: 99, bool: false, array: ['foo' => 'bar']),
        [
            'string' => null,
            'int' => 42,
            'bool' => true,
            'array' => [],
            'object' => new PromotedProperties(string: 'bar', int: 99, bool: false),
        ],
    ],
    'mixed constructor promoted properties' => [
        new PromotedMixedProperties(int: 99, bool: false, array: ['foo' => 'bar']),
        ['null' => null, 'string' => 'foo', 'int' => 42, 'bool' => true],
    ],
]);
