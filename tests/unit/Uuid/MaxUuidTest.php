<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid;

use BadMethodCallException;
use Identifier\Uuid\Variant;
use InvalidArgumentException;
use Ramsey\Identifier\Exception\NotComparableException;
use Ramsey\Identifier\Uuid;
use Ramsey\Test\Identifier\TestCase;

use function json_encode;
use function serialize;
use function strtoupper;
use function unserialize;

class MaxUuidTest extends TestCase
{
    private Uuid\MaxUuid $maxUuid;

    protected function setUp(): void
    {
        $this->maxUuid = new Uuid\MaxUuid();
    }

    public function testConstructorThrowsExceptionForEmptyUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Max UUID: ""');

        new Uuid\MaxUuid('');
    }

    public function testConstructorThrowsExceptionForInvalidUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Max UUID: "a6a011d2-7433-9d43-9161-1550863792c9"');

        new Uuid\MaxUuid('a6a011d2-7433-9d43-9161-1550863792c9');
    }

    public function testSerialize(): void
    {
        $expected =
            'O:30:"Ramsey\\Identifier\\Uuid\\MaxUuid":1:{s:4:"uuid";s:36:"ffffffff-ffff-ffff-ffff-ffffffffffff";}';
        $serialized = serialize($this->maxUuid);

        $this->assertSame($expected, $serialized);
    }

    public function testCastsToString(): void
    {
        $this->assertSame(Uuid::MAX, (string) $this->maxUuid);
    }

    public function testUnserialize(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Uuid\\MaxUuid":1:{s:4:"uuid";s:36:"ffffffff-ffff-ffff-ffff-ffffffffffff";}';
        $maxUuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\MaxUuid::class, $maxUuid);
        $this->assertSame(Uuid::MAX, (string) $maxUuid);
    }

    public function testUnserializeFailsWhenUuidIsAnEmptyString(): void
    {
        $serialized = 'O:30:"Ramsey\\Identifier\\Uuid\\MaxUuid":1:{s:4:"uuid";s:0:"";}';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Max UUID: ""');

        unserialize($serialized);
    }

    public function testUnserializeFailsForInvalidVersionUuid(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Uuid\\MaxUuid":1:{s:4:"uuid";s:36:"a6a011d2-7433-9d43-9161-1550863792c9";}';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Max UUID: "a6a011d2-7433-9d43-9161-1550863792c9"');

        unserialize($serialized);
    }

    /**
     * @dataProvider compareToProvider
     */
    public function testCompareTo(mixed $other, int $expected): void
    {
        $this->assertSame($expected, $this->maxUuid->compareTo($other));
    }

    /**
     * @return array<string, array{mixed, int}>
     */
    public function compareToProvider(): array
    {
        return [
            'with null' => [null, 1],
            'with int' => [123, 1],
            'with float' => [123.456, 1],
            'with string' => ['foobar', -1],
            'with string Nil UUID' => [Uuid::NIL, 1],
            'with string Nil UUID all caps' => [strtoupper(Uuid::NIL), 1],
            'with string Max UUID' => [Uuid::MAX, 0],
            'with string Max UUID all caps' => [strtoupper(Uuid::MAX), 0],
            'with bool true' => [true, 1],
            'with bool false' => [false, 1],
            'with Stringable class' => [
                new class {
                    public function __toString(): string
                    {
                        return 'foobar';
                    }
                },
                -1,
            ],
            'with NilUuid' => [new Uuid\NilUuid(), 1],
            'with MaxUuid' => [new Uuid\MaxUuid(), 0],
        ];
    }

    public function testCompareToThrowsExceptionWhenNotComparable(): void
    {
        $this->expectException(NotComparableException::class);
        $this->expectExceptionMessage('Comparison with values of type "array" is not supported');

        $this->maxUuid->compareTo([]);
    }

    /**
     * @dataProvider equalsProvider
     */
    public function testEquals(mixed $other, bool $expected): void
    {
        $this->assertSame($expected, $this->maxUuid->equals($other));
    }

    /**
     * @return array<string, array{mixed, bool}>
     */
    public function equalsProvider(): array
    {
        return [
            'with null' => [null, false],
            'with int' => [123, false],
            'with float' => [123.456, false],
            'with string' => ['foobar', false],
            'with string Nil UUID' => [Uuid::NIL, false],
            'with string Nil UUID all caps' => [strtoupper(Uuid::NIL), false],
            'with string Max UUID' => [Uuid::MAX, true],
            'with string Max UUID all caps' => [strtoupper(Uuid::MAX), true],
            'with bool true' => [true, false],
            'with bool false' => [false, false],
            'with Stringable class' => [
                new class {
                    public function __toString(): string
                    {
                        return 'foobar';
                    }
                },
                false,
            ],
            'with NilUuid' => [new Uuid\NilUuid(), false],
            'with MaxUuid' => [new Uuid\MaxUuid(), true],
            'with array' => [[], false],
        ];
    }

    public function testGetVariant(): void
    {
        $this->assertSame(Variant::Rfc4122, $this->maxUuid->getVariant());
    }

    public function testGetVersionThrowsException(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Max UUIDs do not have a version field');

        $this->maxUuid->getVersion();
    }

    public function testJsonSerialize(): void
    {
        $this->assertSame('"' . Uuid::MAX . '"', json_encode($this->maxUuid));
    }

    public function testToString(): void
    {
        $this->assertSame(Uuid::MAX, $this->maxUuid->toString());
    }

    public function testToBytes(): void
    {
        $this->assertSame(
            "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff",
            $this->maxUuid->toBytes(),
        );
    }

    public function testToHexadecimal(): void
    {
        $this->assertSame(
            'ffffffffffffffffffffffffffffffff',
            $this->maxUuid->toHexadecimal(),
        );
    }

    public function testToInteger(): void
    {
        $this->assertSame(
            '340282366920938463463374607431768211455',
            $this->maxUuid->toInteger(),
        );
    }

    public function testToUrn(): void
    {
        $this->assertSame('urn:uuid:' . Uuid::MAX, $this->maxUuid->toUrn());
    }
}
