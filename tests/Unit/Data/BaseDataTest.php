<?php

declare(strict_types=1);

use RecursionGuard\Data\BaseData;
use Tests\Support\Data\DefaultPropertiesData;
use Tests\Support\Data\NoPropertiesData;

covers(BaseData::class);

it('checks if an object is empty', function (BaseData $object, bool $empty) {
    expect($object->empty())->toBe($empty);
})->with([
    'no properties' => fn () => [
        new NoPropertiesData(),
        true,
    ],
    'default properties' => fn () => [
        new DefaultPropertiesData(),
        true,
    ],
    'explicit but default properties' => fn () => [
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
    'some non-default properties' => fn () => [
        new DefaultPropertiesData(
            string: 'bar',
            int: 99,
        ),
        false,
    ],
    'empty properties' => fn () => [
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
    'object property with different type but equal value' => fn () => [
        new DefaultPropertiesData(
            object: (object) ['key' => 'value'],
        ),
        false,
    ],
    'all properties non-empty' => fn () => [
        new DefaultPropertiesData(
            null: null,
            string: 'bar',
            int: 1,
            bool: false,
            array: [1],
            object: new stdClass(),
        ),
        false,
    ],
]);

it('uses public properties as offsets', function (DefaultPropertiesData $object, array $expected) {
    expect($object->offsetExists('null'))->toBeTrue()
        ->and($object->offsetGet('null'))->toBe($expected['null'])
        ->and($object->offsetExists('string'))->toBeTrue()
        ->and($object->offsetGet('string'))->toBe($expected['string'])
        ->and($object->offsetExists('int'))->toBeTrue()
        ->and($object->offsetGet('int'))->toBe($expected['int'])
        ->and($object->offsetExists('bool'))->toBeTrue()
        ->and($object->offsetGet('bool'))->toBe($expected['bool'])
        ->and($object->offsetExists('array'))->toBeTrue()
        ->and($object->offsetGet('array'))->toEqual($expected['array'])
        ->and($object->offsetExists('object'))->toBeTrue()
        ->and($object->offsetGet('object'))->toEqual($expected['object'])
        ->and($object->offsetExists(0))->toBeFalse()
        ->and($object->offsetGet(0))->toBeNull()
        ->and($object->offsetExists(5))->toBeFalse()
        ->and($object->offsetGet(5))->toBeNull()
        ->and($object->offsetExists(null))->toBeFalse()
        ->and($object->offsetGet(null))->toBeNull()
        ->and($object->offsetExists('unknown'))->toBeFalse()
        ->and($object->offsetGet('unknown'))->toBeNull()
        ->and($object->offsetExists('foo'))->toBeFalse()
        ->and($object->offsetGet('foo'))->toBeNull()
        ->and($object->offsetExists('bar'))->toBeFalse()
        ->and($object->offsetGet('bar'))->toBeNull()
        ->and(isset($object['null']))->toBeTrue()
        ->and($object['null'])->toBe($expected['null'])
        ->and(isset($object['string']))->toBeTrue()
        ->and($object['string'])->toBe($expected['string'])
        ->and(isset($object['int']))->toBeTrue()
        ->and($object['int'])->toBe($expected['int'])
        ->and(isset($object['bool']))->toBeTrue()
        ->and($object['bool'])->toBe($expected['bool'])
        ->and(isset($object['array']))->toBeTrue()
        ->and($object['array'])->toEqual($expected['array'])
        ->and(isset($object['object']))->toBeTrue()
        ->and($object['object'])->toEqual($expected['object'])
        ->and(isset($object[0]))->toBeFalse()
        ->and($object[0])->toBeNull()
        ->and(isset($object[5]))->toBeFalse()
        ->and($object[5])->toBeNull()
        ->and(isset($object[null]))->toBeFalse()
        ->and($object[null])->toBeNull()
        ->and(isset($object['unknown']))->toBeFalse()
        ->and($object['unknown'])->toBeNull()
        ->and(isset($object['foo']))->toBeFalse()
        ->and($object['foo'])->toBeNull()
        ->and(isset($object['foo']))->toBeFalse()
        ->and($object['bar'])->toBeNull();
})->with('objects');

it('converts public properties to jsonable array', function (DefaultPropertiesData $object, array $expected) {
    expect($object->jsonSerialize())
        ->toEqual($expected)
        ->and(json_encode($object))
        ->toEqual(json_encode($expected));
})->with('objects');

dataset('objects', [
    'defaults' => fn () => [
        new DefaultPropertiesData(),
        ['null' => null, 'string' => 'foo', 'int' => 42, 'bool' => true, 'array' => [1, 2, 3], 'object' => new stdClass()],
    ],
    'empty properties' => fn () => [
        new DefaultPropertiesData(string: '', int: 0, bool: false, array: [], object: new stdClass()),
        ['null' => null, 'string' => '', 'int' => 0, 'bool' => false, 'array' => [], 'object' => new stdClass()],
    ],
    'some changes' => fn () => [
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
    'all changes' => fn () => [
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
