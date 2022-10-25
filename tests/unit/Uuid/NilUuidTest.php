<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid;

use Ramsey\Identifier\Exception\BadMethodCall;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Exception\NotComparable;
use Ramsey\Identifier\Ulid\NilUlid;
use Ramsey\Identifier\Uuid;
use Ramsey\Identifier\Uuid\Variant;
use Ramsey\Test\Identifier\Comparison;
use Ramsey\Test\Identifier\MockBinaryIdentifier;
use Ramsey\Test\Identifier\TestCase;

use function json_encode;
use function serialize;
use function sprintf;
use function unserialize;

class NilUuidTest extends TestCase
{
    private const NIL_UUID = '00000000-0000-0000-0000-000000000000';

    private Uuid\NilUuid $nilUuid;
    private Uuid\NilUuid $nilUuidWithString;
    private Uuid\NilUuid $nilUuidWithHex;
    private Uuid\NilUuid $nilUuidWithBytes;

    protected function setUp(): void
    {
        $this->nilUuid = new Uuid\NilUuid();
        $this->nilUuidWithString = new Uuid\NilUuid(self::NIL_UUID);
        $this->nilUuidWithHex = new Uuid\NilUuid('00000000000000000000000000000000');
        $this->nilUuidWithBytes = new Uuid\NilUuid("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00");
    }

    /**
     * @dataProvider invalidUuidsProvider
     */
    public function testConstructorThrowsExceptionForInvalidUuid(string $value): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(sprintf('Invalid Nil UUID: "%s"', $value));

        new Uuid\NilUuid($value);
    }

    /**
     * @return array<array{value: string, messageValue?: string}>
     */
    public function invalidUuidsProvider(): array
    {
        return [
            ['value' => ''],

            // This is 35 characters:
            ['value' => '00000000-0000-0000-0000-00000000000'],

            // This is 31 characters:
            ['value' => '0000000000000000000000000000000'],

            // This is 15 bytes:
            ['value' => "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"],

            // These 16 bytes don't form a standard UUID:
            ['value' => 'foobarbazquux123'],

            // These contain invalid characters:
            ['value' => '00000000-0000-0000-0000-00000000000g'],
            ['value' => '0000000000000000000000000000000g'],
            ['value' => '00000000-0000-0000-0000-00000000'],

            // Valid Max UUID:
            ['value' => 'ffffffff-ffff-ffff-ffff-ffffffffffff'],
            ['value' => 'ffffffffffffffffffffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff"],

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
            'O:30:"Ramsey\\Identifier\\Uuid\\NilUuid":1:{s:4:"uuid";s:36:"00000000-0000-0000-0000-000000000000";}';
        $serialized = serialize($this->nilUuidWithString);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForHex(): void
    {
        $expected =
            'O:30:"Ramsey\\Identifier\\Uuid\\NilUuid":1:{s:4:"uuid";s:32:"00000000000000000000000000000000";}';
        $serialized = serialize($this->nilUuidWithHex);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForBytes(): void
    {
        $expected =
            'O:30:"Ramsey\\Identifier\\Uuid\\NilUuid":1:{s:4:"uuid";s:16:'
            . "\"\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\";}";
        $serialized = serialize($this->nilUuidWithBytes);

        $this->assertSame($expected, $serialized);
    }

    public function testCastsToString(): void
    {
        $this->assertSame(self::NIL_UUID, (string) $this->nilUuid);
        $this->assertSame(self::NIL_UUID, (string) $this->nilUuidWithString);
        $this->assertSame(self::NIL_UUID, (string) $this->nilUuidWithHex);
        $this->assertSame(self::NIL_UUID, (string) $this->nilUuidWithBytes);
    }

    public function testUnserializeForString(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Uuid\\NilUuid":1:{s:4:"uuid";s:36:"00000000-0000-0000-0000-000000000000";}';
        $nilUuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\NilUuid::class, $nilUuid);
        $this->assertSame(self::NIL_UUID, (string) $nilUuid);
    }

    public function testUnserializeForHex(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Uuid\\NilUuid":1:{s:4:"uuid";s:32:"00000000000000000000000000000000";}';
        $nilUuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\NilUuid::class, $nilUuid);
        $this->assertSame(self::NIL_UUID, (string) $nilUuid);
    }

    public function testUnserializeForBytes(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Uuid\\NilUuid":1:{s:4:"uuid";s:16:'
            . "\"\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\";}";
        $nilUuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\NilUuid::class, $nilUuid);
        $this->assertSame(self::NIL_UUID, (string) $nilUuid);
    }

    public function testUnserializeFailsWhenUuidIsAnEmptyString(): void
    {
        $serialized = 'O:30:"Ramsey\\Identifier\\Uuid\\NilUuid":1:{s:4:"uuid";s:0:"";}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid Nil UUID: ""');

        unserialize($serialized);
    }

    public function testUnserializeFailsForInvalidVersionUuid(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Uuid\\NilUuid":1:{s:4:"uuid";s:36:"a6a011d2-7433-9d43-9161-1550863792c9";}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid Nil UUID: "a6a011d2-7433-9d43-9161-1550863792c9"');

        unserialize($serialized);
    }

    /**
     * @dataProvider compareToProvider
     */
    public function testCompareTo(mixed $other, Comparison $comparison): void
    {
        switch ($comparison) {
            case Comparison::Equal:
                $this->assertSame(0, $this->nilUuid->compareTo($other));
                $this->assertSame(0, $this->nilUuidWithString->compareTo($other));
                $this->assertSame(0, $this->nilUuidWithHex->compareTo($other));
                $this->assertSame(0, $this->nilUuidWithBytes->compareTo($other));

                break;
            case Comparison::GreaterThan:
                $this->assertGreaterThan(0, $this->nilUuid->compareTo($other));
                $this->assertGreaterThan(0, $this->nilUuidWithString->compareTo($other));
                $this->assertGreaterThan(0, $this->nilUuidWithHex->compareTo($other));
                $this->assertGreaterThan(0, $this->nilUuidWithBytes->compareTo($other));

                break;
            case Comparison::LessThan:
                $this->assertLessThan(0, $this->nilUuid->compareTo($other));
                $this->assertLessThan(0, $this->nilUuidWithString->compareTo($other));
                $this->assertLessThan(0, $this->nilUuidWithHex->compareTo($other));
                $this->assertLessThan(0, $this->nilUuidWithBytes->compareTo($other));

                break;
            default:
                $this->markAsRisky();

                break;
        }
    }

    /**
     * @return array<string, array{mixed, Comparison}>
     */
    public function compareToProvider(): array
    {
        return [
            'with null' => [null, Comparison::GreaterThan],
            'with int' => [123, Comparison::LessThan],
            'with float' => [123.456, Comparison::LessThan],
            'with string' => ['foobar', Comparison::LessThan],
            'with string Nil UUID' => [self::NIL_UUID, Comparison::Equal],
            'with hex Nil UUID' => ['00000000000000000000000000000000', Comparison::Equal],
            'with bytes Nil UUID' => [
                "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
                Comparison::Equal,
            ],
            'with string Max UUID' => ['ffffffff-ffff-ffff-ffff-ffffffffffff', Comparison::LessThan],
            'with string Max UUID all caps' => ['FFFFFFFF-FFFF-FFFF-FFFF-FFFFFFFFFFFF', Comparison::LessThan],
            'with bool true' => [true, Comparison::LessThan],
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
                        return "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";
                    }
                },
                Comparison::Equal,
            ],
            'with NilUuid' => [new Uuid\NilUuid(), Comparison::Equal],
            'with NilUuid from string' => [new Uuid\NilUuid(self::NIL_UUID), Comparison::Equal],
            'with NilUuid from hex' => [new Uuid\NilUuid('00000000000000000000000000000000'), Comparison::Equal],
            'with NilUuid from bytes' => [
                new Uuid\NilUuid("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"),
                Comparison::Equal,
            ],
            'with MaxUuid' => [new Uuid\MaxUuid(), Comparison::LessThan],
            'with BinaryIdentifier class' => [
                new MockBinaryIdentifier("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"),
                Comparison::Equal,
            ],
            'with NilUlid' => [new NilUlid(), Comparison::Equal],
        ];
    }

    public function testCompareToThrowsExceptionWhenNotComparable(): void
    {
        $this->expectException(NotComparable::class);
        $this->expectExceptionMessage('Comparison with values of type "array" is not supported');

        $this->nilUuid->compareTo([]);
    }

    /**
     * @dataProvider equalsProvider
     */
    public function testEquals(mixed $other, Comparison $comparison): void
    {
        switch ($comparison) {
            case Comparison::Equal:
                $this->assertTrue($this->nilUuid->equals($other));
                $this->assertTrue($this->nilUuidWithString->equals($other));
                $this->assertTrue($this->nilUuidWithHex->equals($other));
                $this->assertTrue($this->nilUuidWithBytes->equals($other));

                break;
            case Comparison::NotEqual:
                $this->assertFalse($this->nilUuid->equals($other));
                $this->assertFalse($this->nilUuidWithString->equals($other));
                $this->assertFalse($this->nilUuidWithHex->equals($other));
                $this->assertFalse($this->nilUuidWithBytes->equals($other));

                break;
            default:
                $this->markAsRisky();

                break;
        }
    }

    /**
     * @return array<string, array{mixed, Comparison}>
     */
    public function equalsProvider(): array
    {
        return [
            'with null' => [null, Comparison::NotEqual],
            'with int' => [123, Comparison::NotEqual],
            'with float' => [123.456, Comparison::NotEqual],
            'with string' => ['foobar', Comparison::NotEqual],
            'with string Nil UUID' => [self::NIL_UUID, Comparison::Equal],
            'with hex Nil UUID' => ['00000000000000000000000000000000', Comparison::Equal],
            'with bytes Nil UUID' => [
                "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
                Comparison::Equal,
            ],
            'with string Max UUID' => ['ffffffff-ffff-ffff-ffff-ffffffffffff', Comparison::NotEqual],
            'with string Max UUID all caps' => ['FFFFFFFF-FFFF-FFFF-FFFF-FFFFFFFFFFFF', Comparison::NotEqual],
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
                        return "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";
                    }
                },
                Comparison::Equal,
            ],
            'with NilUuid' => [new Uuid\NilUuid(), Comparison::Equal],
            'with NilUuid from string' => [new Uuid\NilUuid(self::NIL_UUID), Comparison::Equal],
            'with NilUuid from hex' => [new Uuid\NilUuid('00000000000000000000000000000000'), Comparison::Equal],
            'with NilUuid from bytes' => [
                new Uuid\NilUuid("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"),
                Comparison::Equal,
            ],
            'with MaxUuid' => [new Uuid\MaxUuid(), Comparison::NotEqual],
            'with array' => [[], Comparison::NotEqual],
            'with BinaryIdentifier class' => [
                new MockBinaryIdentifier("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"),
                Comparison::Equal,
            ],
            'with NilUlid' => [new NilUlid(), Comparison::Equal],
        ];
    }

    public function testGetVariant(): void
    {
        $this->assertSame(Variant::Rfc4122, $this->nilUuid->getVariant());
        $this->assertSame(Variant::Rfc4122, $this->nilUuidWithString->getVariant());
        $this->assertSame(Variant::Rfc4122, $this->nilUuidWithHex->getVariant());
        $this->assertSame(Variant::Rfc4122, $this->nilUuidWithBytes->getVariant());
    }

    public function testGetVersionThrowsException(): void
    {
        $this->expectException(BadMethodCall::class);
        $this->expectExceptionMessage('Nil UUIDs do not have a version field');

        $this->nilUuid->getVersion();
    }

    public function testJsonSerialize(): void
    {
        $this->assertSame('"' . self::NIL_UUID . '"', json_encode($this->nilUuid));
        $this->assertSame('"' . self::NIL_UUID . '"', json_encode($this->nilUuidWithString));
        $this->assertSame('"' . self::NIL_UUID . '"', json_encode($this->nilUuidWithHex));
        $this->assertSame('"' . self::NIL_UUID . '"', json_encode($this->nilUuidWithBytes));
    }

    public function testToString(): void
    {
        $this->assertSame(self::NIL_UUID, $this->nilUuid->toString());
        $this->assertSame(self::NIL_UUID, $this->nilUuidWithString->toString());
        $this->assertSame(self::NIL_UUID, $this->nilUuidWithHex->toString());
        $this->assertSame(self::NIL_UUID, $this->nilUuidWithBytes->toString());
    }

    public function testToBytes(): void
    {
        $bytes = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";

        $this->assertSame($bytes, $this->nilUuid->toBytes());
        $this->assertSame($bytes, $this->nilUuidWithString->toBytes());
        $this->assertSame($bytes, $this->nilUuidWithHex->toBytes());
        $this->assertSame($bytes, $this->nilUuidWithBytes->toBytes());
    }

    public function testToHexadecimal(): void
    {
        $hex = '00000000000000000000000000000000';

        $this->assertSame($hex, $this->nilUuid->toHexadecimal());
        $this->assertSame($hex, $this->nilUuidWithString->toHexadecimal());
        $this->assertSame($hex, $this->nilUuidWithHex->toHexadecimal());
        $this->assertSame($hex, $this->nilUuidWithBytes->toHexadecimal());
    }

    public function testToInteger(): void
    {
        $this->assertSame('0', $this->nilUuid->toInteger());
        $this->assertSame('0', $this->nilUuidWithString->toInteger());
        $this->assertSame('0', $this->nilUuidWithHex->toInteger());
        $this->assertSame('0', $this->nilUuidWithBytes->toInteger());
    }

    public function testToUrn(): void
    {
        $this->assertSame('urn:uuid:' . self::NIL_UUID, $this->nilUuid->toUrn());
        $this->assertSame('urn:uuid:' . self::NIL_UUID, $this->nilUuidWithString->toUrn());
        $this->assertSame('urn:uuid:' . self::NIL_UUID, $this->nilUuidWithHex->toUrn());
        $this->assertSame('urn:uuid:' . self::NIL_UUID, $this->nilUuidWithBytes->toUrn());
    }

    /**
     * Yes, yes. There's no real "lowercase" conversion with a Nil UUID, but
     * we're also testing the equality/comparison check with hex/bytes.
     *
     * @dataProvider valuesForLowercaseConversionTestProvider
     */
    public function testLowercaseConversion(string $value, string $expected): void
    {
        $uuid = new Uuid\NilUuid($value);

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
                'value' => '00000000-0000-0000-0000-000000000000',
                'expected' => self::NIL_UUID,
            ],
            [
                'value' => '00000000000000000000000000000000',
                'expected' => self::NIL_UUID,
            ],
            [
                'value' => "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
                'expected' => self::NIL_UUID,
            ],
        ];
    }
}
