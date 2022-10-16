<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid;

use Ramsey\Identifier\Exception\BadMethodCall;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Exception\NotComparable;
use Ramsey\Identifier\Uuid;
use Ramsey\Identifier\Uuid\Variant;
use Ramsey\Test\Identifier\TestCase;

use function json_encode;
use function serialize;
use function sprintf;
use function strtoupper;
use function unserialize;

class MaxUuidTest extends TestCase
{
    private Uuid\MaxUuid $maxUuid;
    private Uuid\MaxUuid $maxUuidWithString;
    private Uuid\MaxUuid $maxUuidWithHex;
    private Uuid\MaxUuid $maxUuidWithBytes;

    protected function setUp(): void
    {
        $this->maxUuid = new Uuid\MaxUuid();
        $this->maxUuidWithString = new Uuid\MaxUuid('ffffffff-ffff-ffff-ffff-ffffffffffff');
        $this->maxUuidWithHex = new Uuid\MaxUuid('ffffffffffffffffffffffffffffffff');
        $this->maxUuidWithBytes = new Uuid\MaxUuid("\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff");
    }

    /**
     * @dataProvider invalidUuidsProvider
     */
    public function testConstructorThrowsExceptionForInvalidUuid(string $value): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(sprintf('Invalid Max UUID: "%s"', $value));

        new Uuid\MaxUuid($value);
    }

    /**
     * @return array<array{value: string, messageValue?: string}>
     */
    public function invalidUuidsProvider(): array
    {
        return [
            ['value' => ''],

            // This is 35 characters:
            ['value' => 'ffffffff-ffff-ffff-ffff-fffffffffff'],

            // This is 31 characters:
            ['value' => 'ffffffffffffffffffffffffffffff'],

            // This is 15 bytes:
            ['value' => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff"],

            // These 16 bytes don't form a standard UUID:
            ['value' => 'foobarbazquux123'],

            // These contain invalid characters:
            ['value' => 'ffffffff-ffff-ffff-ffff-fffffffffffg'],
            ['value' => 'fffffffffffffffffffffffffffffffg'],
            ['value' => 'ffffffff-ffff-ffff-ffff-ffffffff'],

            // Valid Nil UUID:
            ['value' => '00000000-0000-0000-0000-000000000000'],
            ['value' => '00000000000000000000000000000000'],
            ['value' => "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"],

            // Valid version 1 UUID:
            ['value' => 'ffffffff-ffff-1fff-9fff-ffffffffffff'],
            ['value' => 'ffffffffffff1fff9fffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\x1f\xff\x9f\xff\xff\xff\xff\xff\xff\xff"],

            // Valid version 2 UUID:
            ['value' => 'ffffffff-ffff-2fff-9fff-ffffffffffff'],
            ['value' => 'ffffffffffff2fff9fffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\x2f\xff\x9f\xff\xff\xff\xff\xff\xff\xff"],

            // Valid version 3 UUID:
            ['value' => 'ffffffff-ffff-3fff-9fff-ffffffffffff'],
            ['value' => 'ffffffffffff3fff9fffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\x3f\xff\x9f\xff\xff\xff\xff\xff\xff\xff"],

            // Valid version 4 UUID:
            ['value' => 'ffffffff-ffff-4fff-9fff-ffffffffffff'],
            ['value' => 'ffffffffffff4fff9fffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\x4f\xff\x9f\xff\xff\xff\xff\xff\xff\xff"],

            // Valid version 5 UUID:
            ['value' => 'ffffffff-ffff-5fff-9fff-ffffffffffff'],
            ['value' => 'ffffffffffff5fff9fffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\x5f\xff\x9f\xff\xff\xff\xff\xff\xff\xff"],

            // Valid version 6 UUID:
            ['value' => 'ffffffff-ffff-6fff-9fff-ffffffffffff'],
            ['value' => 'ffffffffffff6fff9fffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\x6f\xff\x9f\xff\xff\xff\xff\xff\xff\xff"],

            // Valid version 7 UUID:
            ['value' => 'ffffffff-ffff-7fff-9fff-ffffffffffff'],
            ['value' => 'ffffffffffff7fff9fffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\x7f\xff\x9f\xff\xff\xff\xff\xff\xff\xff"],

            // Valid version 8 UUID:
            ['value' => 'ffffffff-ffff-8fff-9fff-ffffffffffff'],
            ['value' => 'ffffffffffff8fff9fffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\x8f\xff\x9f\xff\xff\xff\xff\xff\xff\xff"],
        ];
    }

    public function testSerializeForString(): void
    {
        $expected =
            'O:30:"Ramsey\\Identifier\\Uuid\\MaxUuid":1:{s:4:"uuid";s:36:"ffffffff-ffff-ffff-ffff-ffffffffffff";}';
        $serialized = serialize($this->maxUuidWithString);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForHex(): void
    {
        $expected =
            'O:30:"Ramsey\\Identifier\\Uuid\\MaxUuid":1:{s:4:"uuid";s:32:"ffffffffffffffffffffffffffffffff";}';
        $serialized = serialize($this->maxUuidWithHex);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForBytes(): void
    {
        $expected =
            'O:30:"Ramsey\\Identifier\\Uuid\\MaxUuid":1:{s:4:"uuid";s:16:'
            . "\"\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\";}";
        $serialized = serialize($this->maxUuidWithBytes);

        $this->assertSame($expected, $serialized);
    }

    public function testCastsToString(): void
    {
        $this->assertSame(Uuid::max()->toString(), (string) $this->maxUuid);
        $this->assertSame(Uuid::max()->toString(), (string) $this->maxUuidWithString);
        $this->assertSame(Uuid::max()->toString(), (string) $this->maxUuidWithHex);
        $this->assertSame(Uuid::max()->toString(), (string) $this->maxUuidWithBytes);
    }

    public function testUnserializeForString(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Uuid\\MaxUuid":1:{s:4:"uuid";s:36:"ffffffff-ffff-ffff-ffff-ffffffffffff";}';
        $maxUuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\MaxUuid::class, $maxUuid);
        $this->assertSame(Uuid::max()->toString(), (string) $maxUuid);
    }

    public function testUnserializeForHex(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Uuid\\MaxUuid":1:{s:4:"uuid";s:32:"ffffffffffffffffffffffffffffffff";}';
        $maxUuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\MaxUuid::class, $maxUuid);
        $this->assertSame(Uuid::max()->toString(), (string) $maxUuid);
    }

    public function testUnserializeForBytes(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Uuid\\MaxUuid":1:{s:4:"uuid";s:16:'
            . "\"\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\";}";
        $maxUuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\MaxUuid::class, $maxUuid);
        $this->assertSame(Uuid::max()->toString(), (string) $maxUuid);
    }

    public function testUnserializeFailsWhenUuidIsAnEmptyString(): void
    {
        $serialized = 'O:30:"Ramsey\\Identifier\\Uuid\\MaxUuid":1:{s:4:"uuid";s:0:"";}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid Max UUID: ""');

        unserialize($serialized);
    }

    public function testUnserializeFailsForInvalidVersionUuid(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Uuid\\MaxUuid":1:{s:4:"uuid";s:36:"a6a011d2-7433-9d43-9161-1550863792c9";}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid Max UUID: "a6a011d2-7433-9d43-9161-1550863792c9"');

        unserialize($serialized);
    }

    /**
     * @dataProvider compareToProvider
     */
    public function testCompareTo(mixed $other, int $expected): void
    {
        $this->assertSame($expected, $this->maxUuid->compareTo($other));
        $this->assertSame($expected, $this->maxUuidWithString->compareTo($other));
        $this->assertSame($expected, $this->maxUuidWithHex->compareTo($other));
        $this->assertSame($expected, $this->maxUuidWithBytes->compareTo($other));
    }

    /**
     * @return array<string, array{mixed, int}>
     */
    public function compareToProvider(): array
    {
        return [
            'with null' => [null, 36],
            'with int' => [123, 53],
            'with float' => [123.456, 53],
            'with string' => ['foobar', -9],
            'with string Nil UUID' => [Uuid::nil()->toString(), 54],
            'with string Max UUID' => [Uuid::max()->toString(), 0],
            'with string Max UUID all caps' => [strtoupper(Uuid::max()->toString()), 0],
            'with hex Max UUID' => ['ffffffffffffffffffffffffffffffff', 0],
            'with hex Max UUID all caps' => ['FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF', 0],
            'with bytes Max UUID' => ["\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff", 0],
            'with bool true' => [true, 53],
            'with bool false' => [false, 36],
            'with Stringable class' => [
                new class {
                    public function __toString(): string
                    {
                        return 'foobar';
                    }
                },
                -9,
            ],
            'with Stringable class returning UUID bytes' => [
                new class {
                    public function __toString(): string
                    {
                        return "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff";
                    }
                },
                0,
            ],
            'with NilUuid' => [new Uuid\NilUuid(), 54],
            'with MaxUuid' => [new Uuid\MaxUuid(), 0],
            'with MaxUuid from string' => [new Uuid\MaxUuid('ffffffff-ffff-ffff-ffff-ffffffffffff'), 0],
            'with MaxUuid from hex' => [new Uuid\MaxUuid('ffffffffffffffffffffffffffffffff'), 0],
            'with MaxUuid from bytes' => [
                new Uuid\MaxUuid("\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff"),
                0,
            ],
        ];
    }

    public function testCompareToThrowsExceptionWhenNotComparable(): void
    {
        $this->expectException(NotComparable::class);
        $this->expectExceptionMessage('Comparison with values of type "array" is not supported');

        $this->maxUuid->compareTo([]);
    }

    /**
     * @dataProvider equalsProvider
     */
    public function testEquals(mixed $other, bool $expected): void
    {
        $this->assertSame($expected, $this->maxUuid->equals($other));
        $this->assertSame($expected, $this->maxUuidWithString->equals($other));
        $this->assertSame($expected, $this->maxUuidWithHex->equals($other));
        $this->assertSame($expected, $this->maxUuidWithBytes->equals($other));
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
            'with string Nil UUID' => [Uuid::nil()->toString(), false],
            'with string Max UUID' => [Uuid::max()->toString(), true],
            'with string Max UUID all caps' => [strtoupper(Uuid::max()->toString()), true],
            'with hex Max UUID' => ['ffffffffffffffffffffffffffffffff', true],
            'with hex Max UUID all caps' => ['FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF', true],
            'with bytes Max UUID' => ["\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff", true],
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
            'with Stringable class returning UUID bytes' => [
                new class {
                    public function __toString(): string
                    {
                        return "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff";
                    }
                },
                true,
            ],
            'with NilUuid' => [new Uuid\NilUuid(), false],
            'with MaxUuid' => [new Uuid\MaxUuid(), true],
            'with MaxUuid from string' => [new Uuid\MaxUuid('ffffffff-ffff-ffff-ffff-ffffffffffff'), true],
            'with MaxUuid from hex' => [new Uuid\MaxUuid('ffffffffffffffffffffffffffffffff'), true],
            'with MaxUuid from bytes' => [
                new Uuid\MaxUuid("\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff"),
                true,
            ],
            'with array' => [[], false],
        ];
    }

    public function testGetVariant(): void
    {
        $this->assertSame(Variant::Rfc4122, $this->maxUuid->getVariant());
        $this->assertSame(Variant::Rfc4122, $this->maxUuidWithString->getVariant());
        $this->assertSame(Variant::Rfc4122, $this->maxUuidWithHex->getVariant());
        $this->assertSame(Variant::Rfc4122, $this->maxUuidWithBytes->getVariant());
    }

    public function testGetVersionThrowsException(): void
    {
        $this->expectException(BadMethodCall::class);
        $this->expectExceptionMessage('Max UUIDs do not have a version field');

        $this->maxUuid->getVersion();
    }

    public function testJsonSerialize(): void
    {
        $this->assertSame('"' . Uuid::max()->toString() . '"', json_encode($this->maxUuid));
        $this->assertSame('"' . Uuid::max()->toString() . '"', json_encode($this->maxUuidWithString));
        $this->assertSame('"' . Uuid::max()->toString() . '"', json_encode($this->maxUuidWithHex));
        $this->assertSame('"' . Uuid::max()->toString() . '"', json_encode($this->maxUuidWithBytes));
    }

    public function testToString(): void
    {
        $this->assertSame(Uuid::max()->toString(), $this->maxUuid->toString());
        $this->assertSame(Uuid::max()->toString(), $this->maxUuidWithString->toString());
        $this->assertSame(Uuid::max()->toString(), $this->maxUuidWithHex->toString());
        $this->assertSame(Uuid::max()->toString(), $this->maxUuidWithBytes->toString());
    }

    public function testToBytes(): void
    {
        $bytes = "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff";

        $this->assertSame($bytes, $this->maxUuid->toBytes());
        $this->assertSame($bytes, $this->maxUuidWithString->toBytes());
        $this->assertSame($bytes, $this->maxUuidWithHex->toBytes());
        $this->assertSame($bytes, $this->maxUuidWithBytes->toBytes());
    }

    public function testToHexadecimal(): void
    {
        $hex = 'ffffffffffffffffffffffffffffffff';

        $this->assertSame($hex, $this->maxUuid->toHexadecimal());
        $this->assertSame($hex, $this->maxUuidWithString->toHexadecimal());
        $this->assertSame($hex, $this->maxUuidWithHex->toHexadecimal());
        $this->assertSame($hex, $this->maxUuidWithBytes->toHexadecimal());
    }

    public function testToInteger(): void
    {
        $int = '340282366920938463463374607431768211455';

        $this->assertSame($int, $this->maxUuid->toInteger());
        $this->assertSame($int, $this->maxUuidWithString->toInteger());
        $this->assertSame($int, $this->maxUuidWithHex->toInteger());
        $this->assertSame($int, $this->maxUuidWithBytes->toInteger());
    }

    public function testToUrn(): void
    {
        $this->assertSame('urn:uuid:' . Uuid::max()->toString(), $this->maxUuid->toUrn());
        $this->assertSame('urn:uuid:' . Uuid::max()->toString(), $this->maxUuidWithString->toUrn());
        $this->assertSame('urn:uuid:' . Uuid::max()->toString(), $this->maxUuidWithHex->toUrn());
        $this->assertSame('urn:uuid:' . Uuid::max()->toString(), $this->maxUuidWithBytes->toUrn());
    }

    /**
     * @dataProvider valuesForLowercaseConversionTestProvider
     */
    public function testLowercaseConversion(string $value, string $expected): void
    {
        $uuid = new Uuid\MaxUuid($value);

        $this->assertTrue($uuid->equals($value));
        $this->assertSame($expected, $uuid->toString());
    }

    /**
     * @return array<array{value: string, expected: string}>
     */
    public function valuesForLowercaseConversionTestProvider(): array
    {
        return [
            [
                'value' => 'FFFFFFFF-FFFF-FFFF-FFFF-FFFFFFFFFFFF',
                'expected' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
            ],
            [
                'value' => 'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF',
                'expected' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
            ],
            [
                'value' => "\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF",
                'expected' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
            ],
        ];
    }
}
