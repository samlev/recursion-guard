<?php

declare(strict_types=1);

use RecursionGuard\Helper\Obj;
use Tests\Support\Data\PrivatePropertiesWithDefaults;
use Tests\Support\Data\PromotedProperties;
use Tests\Support\Data\ProtectedPropertiesWithDefaults;
use Tests\Support\Data\PublicPropertiesWithDefaults;

covers(Obj::class);

it('returns default values for class properties from class string', function (object $class, array $expected) {
    expect(Obj::defaults(get_class($class)))->toEqual($expected);
})->with('classes');

it('returns default values for class properties from object', function (object $class, array $expected) {
    expect(Obj::defaults($class))->toEqual($expected);
})->with('classes');

dataset('classes', [
    'standard class' => fn () => [
        new stdClass(),
        [],
    ],
    'class without defaults' => fn () => [
        new class () {
            public string $string;
            public int $int;
            public bool $bool;
            public array $array;
            public object $object;
        },
        [],
    ],
    'class with public defaults' => fn () => [
        new class () {
            public null $null = null;
            public string $string = 'foo';
            public int $int = 42;
            public bool $bool = true;
            public array $array = [];
            public object $object;
        },
        ['null' => null, 'string' => 'foo', 'int' => 42, 'bool' => true, 'array' => []],
    ],
    'nullable properties' => fn () => [
        new class () {
            public ?string $string;
            public ?int $int;
            public ?bool $bool;
            public ?array $array;
            public ?object $object;
        },
        ['string' => null, 'int' => null, 'bool' => null, 'array' => null, 'object' => null],
    ],
    'nullable properties with defaults' => fn () => [
        new class () {
            public ?string $string = 'foo';
            public ?int $int = 42;
            public ?bool $bool = true;
            public ?array $array = [];
        },
        ['string' => 'foo', 'int' => 42, 'bool' => true, 'array' => []],
    ],
    'protected properties with defaults' => fn () => [
        new class () {
            protected null $null = null;
            protected string $string = 'foo';
            protected int $int;
            protected bool $bool = true;
            protected array $array = [];
            protected object $object;
        },
        ['null' => null, 'string' => 'foo', 'bool' => true, 'array' => []],
    ],
    'private properties with defaults' => fn () => [
        new class () {
            private null $null;
            private string $string;
            private int $int = 42;
            private bool $bool = false;
            private array $array = [];
            private object $object;
        },
        ['null' => null, 'int' => 42, 'bool' => false, 'array' => []],
    ],
    'mixed static properties' => fn () => [
        new class () {
            public null $null = null;
            public string $string = 'foo';
            public static int $int = 42;
            protected static bool $bool = true;
            private static array $array = [];
            public static object $object;
        },
        ['null' => null, 'string' => 'foo'],
    ],
    'readonly class' => fn () => [
        new class () {
            public readonly null $null;
            public readonly string $string;
            public readonly int $int;
            public readonly bool $bool;
            public readonly array $array;
        },
        ['null' => null],
    ],
    'readonly nullable class' => fn () => [
        new class () {
            public readonly null $null;
            public readonly ?string $string;
            public readonly ?int $int;
            public readonly ?bool $bool;
            public readonly ?array $array;
        },
        ['null' => null, 'string' => null, 'int' => null, 'bool' => null, 'array' => null],
    ],
    'extended public properties with defaults' => fn () => [
        new class () extends PublicPropertiesWithDefaults {
        },
        ['null' => null, 'string' => 'foo', 'int' => 42, 'bool' => true, 'array' => []],
    ],
    'extended protected properties defaults' => fn () => [
        new class () extends ProtectedPropertiesWithDefaults {
        },
        ['null' => null, 'string' => 'foo', 'int' => 42, 'bool' => true, 'array' => []],
    ],
    'extended private properties defaults' => fn () => [
        new class () extends PrivatePropertiesWithDefaults {
        },
        [],
    ],
    'extended properties with overrides' => fn () => [
        new class () extends PublicPropertiesWithDefaults {
            public string $string = 'bar';
            public int $int = 99;
            public bool $bool = false;
            public array $array = ['foo' => 'bar'];
        },
        ['null' => null, 'string' => 'bar', 'int' => 99, 'bool' => false, 'array' => ['foo' => 'bar']],
    ],
    'constructor promoted properties' => fn () => [
        new class () {
            public function __construct(
                public null $null = null,
                public string $string = 'foo',
                public int $int = 42,
                public bool $bool = true,
                public array $array = [],
                public object $object = new stdClass(),
            ) {
                //
            }
        },
        ['null' => null, 'string' => 'foo', 'int' => 42, 'bool' => true, 'array' => [], 'object' => new stdClass()],
    ],
    'readonly constructor promoted properties' => fn () => [
        new class ('foo', int: 99, bool: false, array: ['foo' => 'bar']) {
            public function __construct(
                public ?string $string,
                public readonly int $int = 42,
                public readonly bool $bool = true,
                public readonly array $array = [],
                public readonly PublicPropertiesWithDefaults $object = new PublicPropertiesWithDefaults(),
            ) {
                //
            }
        },
        [
            'string' => null,
            'int' => 42,
            'bool' => true,
            'array' => [],
            'object' => new PublicPropertiesWithDefaults(),
        ],
    ],
    'mixed constructor promoted properties' => fn () => [
        new class (int: 99, bool: false, array: ['foo' => 'bar']) {
            public null $null;
            protected string $string = 'foo';
            public array $array;
            protected PromotedProperties $object;

            public function __construct(
                public int $int = 42,
                private bool $bool = true,
                array $array = ['bing' => 'bang'],
                ?PromotedProperties $object = null,
            ) {
                $this->array = $array;
                $this->object = $object ?? new PromotedProperties(int: $this->int, bool: $this->bool, array: $array);
            }
        },
        ['null' => null, 'string' => 'foo', 'int' => 42, 'bool' => true],
    ],
    'class with only static properties' => fn () => [
        new class () {
            public static string $static = 'static';
            public string $instance = 'instance';
        },
        ['instance' => 'instance'],
    ],
    'promoted property without default value available' => fn () => [
        new class (param: 'value') {
            public function __construct(public string $param)
            {
            }
        },
        [],
    ],
    'untyped properties' => fn () => [
        new class () {
            public $foo;
            protected $bar;
            private $baz;
        },
        ['foo' => null, 'bar' => null, 'baz' => null],
    ],
]);
