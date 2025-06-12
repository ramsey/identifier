<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Identifier\Exception\BadMethodCall;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Exception\NotComparable;
use Ramsey\Identifier\Ulid\MaxUlid;
use Ramsey\Identifier\Uuid;
use Ramsey\Identifier\Uuid\Variant;
use Ramsey\Test\Identifier\Comparison;
use Ramsey\Test\Identifier\MockBinaryIdentifier;
use Ramsey\Test\Identifier\TestCase;

use function json_encode;
use function serialize;
use function sprintf;
use function strtoupper;
use function unserialize;

class MaxUuidTest extends TestCase
{
    private const MAX_UUID = 'ffffffff-ffff-ffff-ffff-ffffffffffff';

    private Uuid\MaxUuid $maxUuid;
    private Uuid\MaxUuid $maxUuidWithString;
    private Uuid\MaxUuid $maxUuidWithHex;
    private Uuid\MaxUuid $maxUuidWithBytes;

    protected function setUp(): void
    {
        $this->maxUuid = new Uuid\MaxUuid();
        $this->maxUuidWithString = new Uuid\MaxUuid(self::MAX_UUID);
        $this->maxUuidWithHex = new Uuid\MaxUuid('ffffffffffffffffffffffffffffffff');
        $this->maxUuidWithBytes = new Uuid\MaxUuid("\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff");
    }

    /**
     * @param non-empty-string $value
     */
    #[DataProvider('invalidUuidsProvider')]
    public function testConstructorThrowsExceptionForInvalidUuid(string $value): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(sprintf('Invalid Max UUID: "%s"', $value));

        new Uuid\MaxUuid($value);
    }

    /**
     * @return list<array{value: string, messageValue?: string}>
     */
    public static function invalidUuidsProvider(): array
    {
        return [
            ['value' => ''],
            ['value' => '0'],

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
        $this->assertSame(self::MAX_UUID, (string) $this->maxUuid);
        $this->assertSame(self::MAX_UUID, (string) $this->maxUuidWithString);
        $this->assertSame(self::MAX_UUID, (string) $this->maxUuidWithHex);
        $this->assertSame(self::MAX_UUID, (string) $this->maxUuidWithBytes);
    }

    public function testUnserializeForString(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Uuid\\MaxUuid":1:{s:4:"uuid";s:36:"ffffffff-ffff-ffff-ffff-ffffffffffff";}';
        $maxUuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\MaxUuid::class, $maxUuid);
        $this->assertSame(self::MAX_UUID, (string) $maxUuid);
    }

    public function testUnserializeForHex(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Uuid\\MaxUuid":1:{s:4:"uuid";s:32:"ffffffffffffffffffffffffffffffff";}';
        $maxUuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\MaxUuid::class, $maxUuid);
        $this->assertSame(self::MAX_UUID, (string) $maxUuid);
    }

    public function testUnserializeForBytes(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Uuid\\MaxUuid":1:{s:4:"uuid";s:16:'
            . "\"\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\";}";
        $maxUuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\MaxUuid::class, $maxUuid);
        $this->assertSame(self::MAX_UUID, (string) $maxUuid);
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

    #[DataProvider('compareToProvider')]
    public function testCompareTo(mixed $other, Comparison $comparison): void
    {
        switch ($comparison) {
            case Comparison::Equal:
                $this->assertSame(0, $this->maxUuid->compareTo($other));
                $this->assertSame(0, $this->maxUuidWithString->compareTo($other));
                $this->assertSame(0, $this->maxUuidWithHex->compareTo($other));
                $this->assertSame(0, $this->maxUuidWithBytes->compareTo($other));

                break;
            case Comparison::GreaterThan:
                $this->assertGreaterThan(0, $this->maxUuid->compareTo($other));
                $this->assertGreaterThan(0, $this->maxUuidWithString->compareTo($other));
                $this->assertGreaterThan(0, $this->maxUuidWithHex->compareTo($other));
                $this->assertGreaterThan(0, $this->maxUuidWithBytes->compareTo($other));

                break;
            case Comparison::LessThan:
                $this->assertLessThan(0, $this->maxUuid->compareTo($other));
                $this->assertLessThan(0, $this->maxUuidWithString->compareTo($other));
                $this->assertLessThan(0, $this->maxUuidWithHex->compareTo($other));
                $this->assertLessThan(0, $this->maxUuidWithBytes->compareTo($other));

                break;
            default:
                throw new Exception('Comparison not supported');
        }
    }

    /**
     * @return array<string, array{mixed, Comparison}>
     */
    public static function compareToProvider(): array
    {
        return [
            'with null' => [null, Comparison::GreaterThan],
            'with int' => [123, Comparison::GreaterThan],
            'with float' => [123.456, Comparison::GreaterThan],
            'with string' => ['foobar', Comparison::LessThan],
            'with string Nil UUID' => ['00000000-0000-0000-0000-000000000000', Comparison::GreaterThan],
            'with string Max UUID' => [self::MAX_UUID, Comparison::Equal],
            'with string Max UUID all caps' => [strtoupper(self::MAX_UUID), Comparison::Equal],
            'with hex Max UUID' => ['ffffffffffffffffffffffffffffffff', Comparison::Equal],
            'with hex Max UUID all caps' => ['FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF', Comparison::Equal],
            'with bytes Max UUID' => [
                "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff",
                Comparison::Equal,
            ],
            'with bool true' => [true, Comparison::GreaterThan],
            'with bool false' => [false, Comparison::GreaterThan],
            'with Stringable class' => [
                new class {
                    public function __toString(): string
                    {
                        return 'foobar';
                    }
                },
                Comparison::LessThan,
            ],
            'with Stringable class returning UUID bytes' => [
                new class {
                    public function __toString(): string
                    {
                        return "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff";
                    }
                },
                Comparison::Equal,
            ],
            'with NilUuid' => [new Uuid\NilUuid(), Comparison::GreaterThan],
            'with MaxUuid' => [new Uuid\MaxUuid(), Comparison::Equal],
            'with MaxUuid from string' => [new Uuid\MaxUuid(self::MAX_UUID), Comparison::Equal],
            'with MaxUuid from hex' => [new Uuid\MaxUuid('ffffffffffffffffffffffffffffffff'), Comparison::Equal],
            'with MaxUuid from bytes' => [
                new Uuid\MaxUuid("\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff"),
                Comparison::Equal,
            ],
            'with BinaryIdentifier class' => [
                new MockBinaryIdentifier("\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff"),
                Comparison::Equal,
            ],
            'with MaxUlid' => [new MaxUlid(), Comparison::Equal],
        ];
    }

    public function testCompareToThrowsExceptionWhenNotComparable(): void
    {
        $this->expectException(NotComparable::class);
        $this->expectExceptionMessage('Comparison with values of type "array" is not supported');

        $this->maxUuid->compareTo([]);
    }

    #[DataProvider('equalsProvider')]
    public function testEquals(mixed $other, Comparison $comparison): void
    {
        switch ($comparison) {
            case Comparison::Equal:
                $this->assertTrue($this->maxUuid->equals($other));
                $this->assertTrue($this->maxUuidWithString->equals($other));
                $this->assertTrue($this->maxUuidWithHex->equals($other));
                $this->assertTrue($this->maxUuidWithBytes->equals($other));

                break;
            case Comparison::NotEqual:
                $this->assertFalse($this->maxUuid->equals($other));
                $this->assertFalse($this->maxUuidWithString->equals($other));
                $this->assertFalse($this->maxUuidWithHex->equals($other));
                $this->assertFalse($this->maxUuidWithBytes->equals($other));

                break;
            default:
                throw new Exception('Comparison not supported');
        }
    }

    /**
     * @return array<string, array{mixed, Comparison}>
     */
    public static function equalsProvider(): array
    {
        return [
            'with null' => [null, Comparison::NotEqual],
            'with int' => [123, Comparison::NotEqual],
            'with float' => [123.456, Comparison::NotEqual],
            'with string' => ['foobar', Comparison::NotEqual],
            'with string Nil UUID' => ['00000000-0000-0000-0000-000000000000', Comparison::NotEqual],
            'with string Max UUID' => [self::MAX_UUID, Comparison::Equal],
            'with string Max UUID all caps' => [strtoupper(self::MAX_UUID), Comparison::Equal],
            'with hex Max UUID' => ['ffffffffffffffffffffffffffffffff', Comparison::Equal],
            'with hex Max UUID all caps' => ['FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF', Comparison::Equal],
            'with bytes Max UUID' => [
                "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff",
                Comparison::Equal,
            ],
            'with bool true' => [true, Comparison::NotEqual],
            'with bool false' => [false, Comparison::NotEqual],
            'with Stringable class' => [
                new class {
                    public function __toString(): string
                    {
                        return 'foobar';
                    }
                },
                Comparison::NotEqual,
            ],
            'with Stringable class returning UUID bytes' => [
                new class {
                    public function __toString(): string
                    {
                        return "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff";
                    }
                },
                Comparison::Equal,
            ],
            'with NilUuid' => [new Uuid\NilUuid(), Comparison::NotEqual],
            'with MaxUuid' => [new Uuid\MaxUuid(), Comparison::Equal],
            'with MaxUuid from string' => [new Uuid\MaxUuid(self::MAX_UUID), Comparison::Equal],
            'with MaxUuid from hex' => [new Uuid\MaxUuid('ffffffffffffffffffffffffffffffff'), Comparison::Equal],
            'with MaxUuid from bytes' => [
                new Uuid\MaxUuid("\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff"),
                Comparison::Equal,
            ],
            'with array' => [[], Comparison::NotEqual],
            'with BinaryIdentifier class' => [
                new MockBinaryIdentifier("\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff"),
                Comparison::Equal,
            ],
            'with MaxUlid' => [new MaxUlid(), Comparison::Equal],
        ];
    }

    public function testGetVariant(): void
    {
        $this->assertSame(Variant::Future, $this->maxUuid->getVariant());
        $this->assertSame(Variant::Future, $this->maxUuidWithString->getVariant());
        $this->assertSame(Variant::Future, $this->maxUuidWithHex->getVariant());
        $this->assertSame(Variant::Future, $this->maxUuidWithBytes->getVariant());
    }

    public function testGetVersionThrowsException(): void
    {
        $this->expectException(BadMethodCall::class);
        $this->expectExceptionMessage('Max UUIDs do not have a version field');

        $this->maxUuid->getVersion();
    }

    public function testJsonSerialize(): void
    {
        $this->assertSame('"' . self::MAX_UUID . '"', json_encode($this->maxUuid));
        $this->assertSame('"' . self::MAX_UUID . '"', json_encode($this->maxUuidWithString));
        $this->assertSame('"' . self::MAX_UUID . '"', json_encode($this->maxUuidWithHex));
        $this->assertSame('"' . self::MAX_UUID . '"', json_encode($this->maxUuidWithBytes));
    }

    public function testToString(): void
    {
        $this->assertSame(self::MAX_UUID, $this->maxUuid->toString());
        $this->assertSame(self::MAX_UUID, $this->maxUuidWithString->toString());
        $this->assertSame(self::MAX_UUID, $this->maxUuidWithHex->toString());
        $this->assertSame(self::MAX_UUID, $this->maxUuidWithBytes->toString());
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
        $this->assertSame('urn:uuid:' . self::MAX_UUID, $this->maxUuid->toUrn());
        $this->assertSame('urn:uuid:' . self::MAX_UUID, $this->maxUuidWithString->toUrn());
        $this->assertSame('urn:uuid:' . self::MAX_UUID, $this->maxUuidWithHex->toUrn());
        $this->assertSame('urn:uuid:' . self::MAX_UUID, $this->maxUuidWithBytes->toUrn());
    }

    /**
     * @param non-empty-string $value
     */
    #[DataProvider('valuesForLowercaseConversionTestProvider')]
    public function testLowercaseConversion(string $value, string $expected): void
    {
        $uuid = new Uuid\MaxUuid($value);

        $this->assertTrue($uuid->equals($value));
        $this->assertSame($expected, $uuid->toString());
    }

    /**
     * @return list<array{value: string, expected: string}>
     */
    public static function valuesForLowercaseConversionTestProvider(): array
    {
        return [
            [
                'value' => 'FFFFFFFF-FFFF-FFFF-FFFF-FFFFFFFFFFFF',
                'expected' => self::MAX_UUID,
            ],
            [
                'value' => 'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF',
                'expected' => self::MAX_UUID,
            ],
            [
                'value' => "\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF",
                'expected' => self::MAX_UUID,
            ],
        ];
    }
}
