<?php

declare(strict_types=1);

use RecursionGuard\Data\BaseData;
use Tests\Support\Data\NoPropertiesData;
use Tests\Support\Data\NullableProperties;
use Tests\Support\Data\NullablePropertiesWithDefaults;
use Tests\Support\Data\PrivatePropertiesWithDefaults;
use Tests\Support\Data\PromotedMixedProperties;
use Tests\Support\Data\PromotedProperties;
use Tests\Support\Data\DefaultPropertiesData;
use Tests\Support\Data\ProtectedPropertiesWithDefaults;
use Tests\Support\Data\PublicPropertiesWithDefaults;
use Tests\Support\Data\PublicPropertiesWithoutDefaults;
use Tests\Support\Data\ReadonlyClass;
use Tests\Support\Data\ReadonlyNullableClass;
use Tests\Support\Data\ReadonlyPromotedProperties;
use Tests\Support\Data\StaticProperties;

covers(BaseData::class);

it('checks if an object is empty', function (BaseData $object, bool $empty) {
    expect($object->empty())->toBe($empty);
})->with([
    'no properties' => [
        new NoPropertiesData(),
        true,
    ],
    'default properties' => [
        new DefaultPropertiesData(),
        true,
    ],
    'explicit but default properties' => [
        new DefaultPropertiesData(
            null: null,
            string: 'foo',
            int: 42,
            bool: true,
            array: [1, 2, 3],
            object: new stdClass(),
        ),
        true,
    ],
    'some non-default properties' => [
        new DefaultPropertiesData(
            string: 'bar',
            int: 99,
        ),
        false,
    ],
    'empty properties' => [
        new DefaultPropertiesData(
            null: null,
            string: '',
            int: 0,
            bool: false,
            array: [],
            object: new stdClass(),
        ),
        false,
    ],
]);

it('uses public properties as offsets', function (DefaultPropertiesData $object) {
    expect($object->offsetExists('null'))->toBeTrue()
        ->and($object->offsetExists('string'))->toBeTrue()
        ->and($object->offsetExists('int'))->toBeTrue()
        ->and($object->offsetExists('bool'))->toBeTrue()
        ->and($object->offsetExists('array'))->toBeTrue()
        ->and($object->offsetExists('object'))->toBeTrue()
        ->and($object->offsetExists(0))->toBeFalse()
        ->and($object->offsetExists(5))->toBeFalse()
        ->and($object->offsetExists(null))->toBeFalse()
        ->and($object->offsetExists('unknown'))->toBeFalse()
        ->and($object->offsetExists('foo'))->toBeFalse()
        ->and($object->offsetExists('bar'))->toBeFalse()
        ->and(isset($object['null']))->toBeTrue()
        ->and(isset($object['string']))->toBeTrue()
        ->and(isset($object['int']))->toBeTrue()
        ->and(isset($object['bool']))->toBeTrue()
        ->and(isset($object['array']))->toBeTrue()
        ->and(isset($object['object']))->toBeTrue()
        ->and(isset($object[0]))->toBeFalse()
        ->and(isset($object[5]))->toBeFalse()
        ->and(isset($object[null]))->toBeFalse()
        ->and(isset($object['unknown']))->toBeFalse()
        ->and(isset($object['foo']))->toBeFalse()
        ->and(isset($object['bar']))->toBeFalse();
})->with('objects');

it('gets public properties as offsets', function (DefaultPropertiesData $object, array $expected) {
    expect($object->offsetGet('null'))->toBe($expected['null'])
        ->and($object->offsetGet('string'))->toBe($expected['string'])
        ->and($object->offsetGet('int'))->toBe($expected['int'])
        ->and($object->offsetGet('bool'))->toBe($expected['bool'])
        ->and($object->offsetGet('array'))->toEqual($expected['array'])
        ->and($object->offsetGet('object'))->toEqual($expected['object'])
        ->and($object->offsetGet(0))->toBeNull()
        ->and($object->offsetGet(5))->toBeNull()
        ->and($object->offsetGet(null))->toBeNull()
        ->and($object->offsetGet('unknown'))->toBeNull()
        ->and($object->offsetGet('foo'))->toBeNull()
        ->and($object->offsetGet('bar'))->toBeNull()
        ->and($object['null'])->toBe($expected['null'])
        ->and($object['string'])->toBe($expected['string'])
        ->and($object['int'])->toBe($expected['int'])
        ->and($object['bool'])->toBe($expected['bool'])
        ->and($object['array'])->toEqual($expected['array'])
        ->and($object['object'])->toEqual($expected['object'])
        ->and($object[0])->toBeNull()
        ->and($object[5])->toBeNull()
        ->and($object[null])->toBeNull()
        ->and($object['unknown'])->toBeNull()
        ->and($object['foo'])->toBeNull()
        ->and($object['bar'])->toBeNull();
})->with('objects');

it('converts public properties to jsonable array', function (DefaultPropertiesData $object, array $expected) {
    expect($object->jsonSerialize())
        ->toEqual($expected)
        ->and(json_encode($object))
        ->toEqual(json_encode($expected));
})->with('objects');

it('returns default values for class properties from class string', function (object $class, array $expected) {
    expect(BaseData::defaults(get_class($class)))->toEqual($expected);
})->with('classes');

it('returns default values for class properties from object', function (object $class, array $expected) {
    expect(BaseData::defaults($class))->toEqual($expected);
})->with('classes');

dataset('objects', [
    'defaults' => [
        new DefaultPropertiesData(),
        ['null' => null, 'string' => 'foo', 'int' => 42, 'bool' => true, 'array' => [1, 2, 3], 'object' => new stdClass()],
    ],
    'empty properties' => [
        new DefaultPropertiesData(string: '', int: 0, bool: false, array: [], object: new stdClass()),
        ['null' => null, 'string' => '', 'int' => 0, 'bool' => false, 'array' => [], 'object' => new stdClass()],
    ],
    'some changes' => [
        new DefaultPropertiesData(string: 'bar', int: 99, array: ['foo' => 'bar']),
        [
            'null' => null,
            'string' => 'bar',
            'int' => 99,
            'bool' => true,
            'array' => ['foo' => 'bar'],
            'object' => new stdClass(),
        ],
    ],
    'all changes' => [
        new DefaultPropertiesData(
            string: 'baz',
            int: 86,
            bool: false,
            array: ['foo' => 'bar', 'baz', 3 => 200],
            object: (object) ['bing' => 'bang', 'bong' => 9001],
        ),
        [
            'null' => null,
            'string' => 'baz',
            'int' => 86,
            'bool' => false,
            'array' => ['foo' => 'bar', 0 => 'baz', 3 => 200],
            'object' => (object) ['bing' => 'bang', 'bong' => 9001],
        ],
    ],
]);

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


/**
 * @param int $size
 * @return array<int, int|string>
 * @throws \Random\RandomException
 */
function random_array(int $size): array
{
    return array_map(fn ($id) => rand(0, 1) ? random_bytes($id) : rand(PHP_INT_MIN, PHP_INT_MAX), range(3, $size + 2));
}
