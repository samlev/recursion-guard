<?php

declare(strict_types=1);

use RecursionGuard\Data\BaseData;
use Tests\Support\Data\DefaultPropertiesData;
use Tests\Support\Data\NoPropertiesData;

covers(BaseData::class, \RecursionGuard\Helper\Obj::class);

it('treats object with no properties as empty', function () {
    expect((new NoPropertiesData())->empty())->toBeTrue();
});

it('treats object with default properties as empty', function () {
    expect((new DefaultPropertiesData())->empty())->toBeTrue();
});

it('treats object with properties matching defaults as empty', function () {
    expect(
        (new DefaultPropertiesData(
            null: null,
            string: 'foo',
            number: 42,
            bool: true,
            array: [1, 2, 3],
            class: new stdClass(),
        ))->empty()
    )->toBeTrue();
});

it('treats a different object type as not empty', function () {
    expect(
        (new DefaultPropertiesData(
            class: new NoPropertiesData(),
        ))->empty()
    )->toBeFalse();
});

it('treats a different array values as not empty', function () {
    expect(
        (new DefaultPropertiesData(
            array: [3, 2, 1],
        ))->empty()
    )->toBeFalse();
});

it('treats a different string values as not empty', function () {
    expect(
        (new DefaultPropertiesData(
            string: 'bar',
        ))->empty()
    )->toBeFalse();
});

it('treats a different number values as not empty', function () {
    expect(
        (new DefaultPropertiesData(
            number: 4.2,
        ))->empty()
    )->toBeFalse();
});

it('treats a empty values that do not match the default as not empty', function (array $fields) {
    expect((new DefaultPropertiesData(...$fields))->empty())
        ->toBeFalse();
})->with([
    'string' => [['string' => '']],
    'number as integer' => [['number' => 0]],
    'number as float' => [['number' => 0.0]],
    'bool' => [['bool' => false]],
    'array' => [['array' => []]],
    'class' => [['class' => '']],
]);

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
            number: 42,
            bool: true,
            array: [1, 2, 3],
            class: new stdClass(),
        ),
        true,
    ],
    'some non-default properties' => fn () => [
        new DefaultPropertiesData(
            string: 'bar',
            number: 99,
            class: NoPropertiesData::class,
        ),
        false,
    ],
    'empty properties' => fn () => [
        new DefaultPropertiesData(
            null: null,
            string: '',
            number: 0,
            bool: false,
            array: [],
            class: '',
        ),
        false,
    ],
    'object property with different type' => fn () => [
        new DefaultPropertiesData(
            class: new NoPropertiesData(),
        ),
        false,
    ],
    'all properties non-empty' => fn () => [
        new DefaultPropertiesData(
            null: null,
            string: 'bar',
            number: 1,
            bool: false,
            array: [1],
            class: (object) ['key' => 'value'],
        ),
        false,
    ],
]);

it('uses public properties as offsets', function (DefaultPropertiesData $object, array $expected) {
    expect($object->offsetExists('null'))->toBeTrue()
        ->and($object->offsetGet('null'))->toBe($expected['null'])
        ->and($object->offsetExists('string'))->toBeTrue()
        ->and($object->offsetGet('string'))->toBe($expected['string'])
        ->and($object->offsetExists('number'))->toBeTrue()
        ->and($object->offsetGet('number'))->toBe($expected['number'])
        ->and($object->offsetExists('bool'))->toBeTrue()
        ->and($object->offsetGet('bool'))->toBe($expected['bool'])
        ->and($object->offsetExists('array'))->toBeTrue()
        ->and($object->offsetGet('array'))->toEqual($expected['array'])
        ->and($object->offsetExists('class'))->toBeTrue()
        ->and($object->offsetGet('class'))->toEqual($expected['class'])
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
        ->and(isset($object['number']))->toBeTrue()
        ->and($object['number'])->toBe($expected['number'])
        ->and(isset($object['bool']))->toBeTrue()
        ->and($object['bool'])->toBe($expected['bool'])
        ->and(isset($object['array']))->toBeTrue()
        ->and($object['array'])->toEqual($expected['array'])
        ->and(isset($object['class']))->toBeTrue()
        ->and($object['class'])->toEqual($expected['class'])
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

it('rejects updating properties', function ($property, $value) {
    $object = new DefaultPropertiesData();

    $modify = sprintf('Cannot modify readonly property %s::$%s', DefaultPropertiesData::class, $property);
    $unset = sprintf('Cannot unset readonly property %s::$%s', DefaultPropertiesData::class, $property);

    expect(fn () => $object->$property = $value)->toThrow(\Error::class, $modify)
        ->and(fn () => $object[$property] = $value)->toThrow(\Error::class, $modify)
        ->and(fn () => $object->offsetSet($property, $value))->toThrow(\Error::class, $modify)
        ->and(function () use ($object, $property) {
            unset($object->$property);
        })->toThrow(\Error::class, $unset)
        ->and(function () use ($object, $property) {
            unset($object[$property]);
        })->toThrow(\Error::class, $unset)
        ->and(fn () => $object->offsetUnset($property))->toThrow(\Error::class, $unset);
})->with([
    'null' => ['null', null],
    'string' => ['string', 'bar'],
    'number' => ['number', 3.14],
    'bool' => ['bool', false],
    'array' => ['array', [3, 2, 1]],
    'class' => ['class', 'test'],
]);


it('treats loosely-equal arrays as empty values', function () {
    expect((new DefaultPropertiesData(array: ['1', 2, 3]))->empty())->toBeTrue();
});

it('includes qualified offset in dynamic property array access errors', function () {
    $object = new DefaultPropertiesData();
    $property = 'unknown';

    $create = sprintf('Cannot create dynamic property %s::$%s', DefaultPropertiesData::class, $property);
    $unset = sprintf('Cannot unset dynamic property %s::$%s', DefaultPropertiesData::class, $property);

    expect(fn () => $object[$property] = 'value')->toThrow(\Error::class, $create)
        ->and(fn () => $object->offsetSet($property, 'value'))->toThrow(\Error::class, $create)
        ->and(function () use ($object, $property) {
            unset($object[$property]);
        })->toThrow(\Error::class, $unset)
        ->and(fn () => $object->offsetUnset($property))->toThrow(\Error::class, $unset);
});

it('converts public properties to jsonable array', function (DefaultPropertiesData $object, array $expected) {
    expect($object->jsonSerialize())
        ->toEqual($expected)
        ->and(json_encode($object))
        ->toEqual(json_encode($expected));
})->with('objects');

dataset('objects', [
    'defaults' => fn () => [
        new DefaultPropertiesData(),
        ['null' => null, 'string' => 'foo', 'number' => 42, 'bool' => true, 'array' => [1, 2, 3], 'class' => new stdClass()],
    ],
    'empty properties' => fn () => [
        new DefaultPropertiesData(string: '', number: 0, bool: false, array: [], class: new stdClass()),
        ['null' => null, 'string' => '', 'number' => 0, 'bool' => false, 'array' => [], 'class' => new stdClass()],
    ],
    'some changes' => fn () => [
        new DefaultPropertiesData(string: 'bar', number: 99, array: ['foo' => 'bar']),
        [
            'null' => null,
            'string' => 'bar',
            'number' => 99,
            'bool' => true,
            'array' => ['foo' => 'bar'],
            'class' => new stdClass(),
        ],
    ],
    'all changes' => fn () => [
        new DefaultPropertiesData(
            string: 'baz',
            number: 86,
            bool: false,
            array: ['foo' => 'bar', 'baz', 3 => 200],
            class: (object) ['bing' => 'bang', 'bong' => 9001],
        ),
        [
            'null' => null,
            'string' => 'baz',
            'number' => 86,
            'bool' => false,
            'array' => ['foo' => 'bar', 0 => 'baz', 3 => 200],
            'class' => (object) ['bing' => 'bang', 'bong' => 9001],
        ],
    ],
]);
