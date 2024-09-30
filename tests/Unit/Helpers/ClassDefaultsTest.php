<?php

declare(strict_types=1);

use function RecursionGuard\class_defaults;

covers('RecursionGuard\\class_defaults');

it('returns default values for class properties from class string', function (object $class, array $expected) {
    expect(class_defaults(get_class($class)))->toEqual($expected);
})->with('classes');

it('returns default values for class properties from object', function (object $class, array $expected) {
    expect(class_defaults($class))->toEqual($expected);
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
        new class () {
            public static null $null = null;
            public static string $string = 'foo';
            public static int $int = 42;
            public static bool $bool = true;
            public static array $array = [];
        },
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

class PublicPropertiesWithoutDefaults
{
    public string $string;
    public int $int;
    public bool $bool;
    public array $array;
    public object $object;
}

class PublicPropertiesWithDefaults
{
    public null $null = null;
    public string $string = 'foo';
    public int $int = 42;
    public bool $bool = true;
    public array $array = [];
    public object $object;
}

class ProtectedPropertiesWithDefaults
{
    protected null $null = null;
    protected string $string = 'foo';
    protected int $int = 42;
    protected bool $bool = true;
    protected array $array = [];
    protected object $object;
}

class PrivatePropertiesWithDefaults
{
    private null $null = null;
    private string $string = 'foo';
    private int $int = 42;
    private bool $bool = true;
    private array $array = [];
    private object $object;
}

class NullableProperties
{
    public ?string $string;
    public ?int $int;
    public ?bool $bool;
    public ?array $array;
    private ?object $object;
}

class NullablePropertiesWithDefaults
{
    public ?string $string = 'foo';
    public ?int $int = 42;
    public ?bool $bool = true;
    public ?array $array = [];
}

readonly class ReadonlyClass
{
    public null $null;
    public string $string;
    public int $int;
    public bool $bool;
    public array $array;
}

readonly class ReadonlyNullableClass
{
    public null $null;
    public ?string $string;
    public ?int $int;
    public ?bool $bool;
    public ?array $array;
}

class PromotedProperties
{
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
}

class ReadonlyPromotedProperties
{
    public function __construct(
        public ?string $string,
        readonly public int $int = 42,
        readonly public bool $bool = true,
        readonly public array $array = [],
        readonly public PromotedProperties $object = new PromotedProperties(string: 'bar', int: 99, bool: false),
    ) {
        //
    }
}

class PromotedMixedProperties
{
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
}
