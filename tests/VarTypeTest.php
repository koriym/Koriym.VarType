<?php

declare(strict_types=1);

namespace Koriym\VarType;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

use function get_class;

final class VarTypeTest extends TestCase
{
    private VarType $varType;

    protected function setUp(): void
    {
        $this->varType = new VarType();
    }

    public function testScalarTypes(): void
    {
        $this->assertSame('int', ($this->varType)(1));
        $this->assertSame('float', ($this->varType)(1.1));
        $this->assertSame('bool', ($this->varType)(true));
        $this->assertSame('string', ($this->varType)('string'));
        $this->assertSame('null', ($this->varType)(null));
    }

    public function testEmptyArray(): void
    {
        $this->assertSame('array', ($this->varType)([]));
    }

    public function testIndexedArray(): void
    {
        $this->assertSame('array<int>', ($this->varType)([1, 2, 3]));
        $this->assertSame('array<int|float|string>', ($this->varType)([1, 1.1, 'string']));
    }

    public function testAssociativeArray(): void
    {
        $this->assertSame('array{a: int, b: string, c: bool}', ($this->varType)(['a' => 1, 'b' => 'string', 'c' => true]));
    }

    public function testNestedArray(): void
    {
        $this->assertSame('array{a: array<int>, b: array<string>}', ($this->varType)(['a' => [1, 2, 3], 'b' => ['x', 'y', 'z']]));
    }

    public function testEmptyObject(): void
    {
        $emptyObject = new class {
        };
        $className = $emptyObject::class;
        $this->assertSame($className, ($this->varType)($emptyObject));
    }

    public function testSimpleObject(): void
    {
        $simpleObject = new stdClass();
        $simpleObject->name = 'John Doe';
        $simpleObject->age = 30;
        $this->assertSame('stdClass{name: string, age: int}', ($this->varType)($simpleObject));
    }

    public function testObjectWithProperties(): void
    {
        $object = new class {
            public int $a = 1;
            public string $b = 'string';
        };
        $className = $object::class;
        $this->assertSame("{$className}{a: int, b: string}", ($this->varType)($object));
    }

    public function testComplexObject(): void
    {
        $object = new class {
            /** @var array<int>  */
            public array $a = [1, 2, 3];
            public object $b;

            public function __construct()
            {
                $this->b = new class {
                    public string $c = 'string';
                };
            }
        };
        $className = $object::class;
        $nestedClassName = get_class($object->b);
        $this->assertSame("{$className}{a: array<int>, b: {$nestedClassName}{c: string}}", ($this->varType)($object));
    }

    public function testDump(): void
    {
        $this->expectOutputString('array<int>');
        VarType::dump([1, 2, 3]);
    }

    public function testNullValue(): void
    {
        $this->assertSame('null', ($this->varType)(null));
    }

    public function testMixedArray(): void
    {
        $this->assertSame('array{0: int, 1: string, key: bool}', ($this->varType)([0 => 1, 1 => 'str', 'key' => true]));
    }

    public function testComplexStructure(): void
    {
        $user = new FakeUser();
        $user->name = 'Jane Doe';
        $user->age = 28;
        $user->roles = ['admin', 'editor'];
        $complexStructure = [
            'user' => $user,
            'settings' => [
                'theme' => 'dark',
                'notifications' => true,
                'limits' => [10, 20, 30],
            ],
            'metadata' => null,
        ];
        $this->assertSame('array{user: Koriym\VarType\FakeUser{name: string, age: int, roles: array<string>}, settings: array{theme: string, notifications: bool, limits: array<int>}, metadata: null}', ($this->varType)($complexStructure));
    }

    public function testIsAssociativeArray(): void
    {
        $testCases = [
            'Empty array should not be considered associative' => [[], false],
            'Array with sequential numeric keys should not be associative' => [[1, 2, 3], false],
            'Array with string keys should be associative' => [['a' => 1, 'b' => 2], true],
            'Array with mix of numeric and string keys should be associative' => [[0 => 'a', 'b' => 'c'], true],
            'Array with non-sequential numeric keys should be associative' => [[1 => 'a', 3 => 'b'], true],
            'Array with sequential string keys that look like numbers should NOT be associative' => [['0' => 'a', '1' => 'b', '2' => 'c'], false], // https://github.com/php/php-src/issues/9029
            'Array with one element and key 0 should not be associative' => [[0 => 'single'], false],
            'Array with one element and non-zero key should be associative' => [[1 => 'single'], true],
        ];

        foreach ($testCases as $description => [$input, $expected]) {
            $result = $this->invokePrivateMethod($this->varType, 'isAssociativeArray', [$input]);
            $this->assertSame($expected, $result, $description);
        }
    }

    /** @param array<mixed> $parameters */
    private function invokePrivateMethod(object $object, string $methodName, array $parameters = []): bool
    {
        $reflection = new ReflectionClass($object::class);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return (bool) $method->invokeArgs($object, $parameters);
    }
}
