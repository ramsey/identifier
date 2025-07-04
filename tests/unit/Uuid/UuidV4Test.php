<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Exception\NotComparable;
use Ramsey\Identifier\Ulid\Ulid;
use Ramsey\Identifier\Uuid;
use Ramsey\Identifier\Uuid\Variant;
use Ramsey\Identifier\Uuid\Version;
use Ramsey\Test\Identifier\Comparison;
use Ramsey\Test\Identifier\MockBinaryIdentifier;
use Ramsey\Test\Identifier\TestCase;

use function json_encode;
use function serialize;
use function sprintf;
use function strtoupper;
use function unserialize;

class UuidV4Test extends TestCase
{
    private const UUID_V4_STRING = '27433d43-011d-4a6a-9161-1550863792c9';
    private const UUID_V4_HEX = '27433d43011d4a6a91611550863792c9';
    private const UUID_V4_BYTES = "\x27\x43\x3d\x43\x01\x1d\x4a\x6a\x91\x61\x15\x50\x86\x37\x92\xc9";

    private Uuid\UuidV4 $uuidWithString;
    private Uuid\UuidV4 $uuidWithHex;
    private Uuid\UuidV4 $uuidWithBytes;

    protected function setUp(): void
    {
        $this->uuidWithString = new Uuid\UuidV4(self::UUID_V4_STRING);
        $this->uuidWithHex = new Uuid\UuidV4(self::UUID_V4_HEX);
        $this->uuidWithBytes = new Uuid\UuidV4(self::UUID_V4_BYTES);
    }

    /**
     * @param non-empty-string $value
     */
    #[DataProvider('invalidUuidsProvider')]
    public function testConstructorThrowsExceptionForInvalidUuid(string $value): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(sprintf('Invalid version 4 UUID: "%s"', $value));

        new Uuid\UuidV4($value);
    }

    /**
     * @return list<array{value: string, messageValue?: string}>
     */
    public static function invalidUuidsProvider(): array
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

            // Valid Nil UUID:
            ['value' => '00000000-0000-0000-0000-000000000000'],
            ['value' => '00000000000000000000000000000000'],
            ['value' => "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"],

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

            // These appear to have valid versions, but they have invalid variants
            ['value' => 'ffffffff-ffff-1fff-cfff-ffffffffffff'],
            ['value' => 'ffffffffffff1fffcfffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\x1f\xff\xcf\xff\xff\xff\xff\xff\xff\xff"],
            ['value' => 'ffffffff-ffff-2fff-cfff-ffffffffffff'],
            ['value' => 'ffffffffffff2fffcfffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\x2f\xff\xcf\xff\xff\xff\xff\xff\xff\xff"],
            ['value' => 'ffffffff-ffff-3fff-cfff-ffffffffffff'],
            ['value' => 'ffffffffffff3fffcfffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\x3f\xff\xcf\xff\xff\xff\xff\xff\xff\xff"],
            ['value' => 'ffffffff-ffff-4fff-cfff-ffffffffffff'],
            ['value' => 'ffffffffffff4fffcfffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\x4f\xff\xcf\xff\xff\xff\xff\xff\xff\xff"],
            ['value' => 'ffffffff-ffff-5fff-cfff-ffffffffffff'],
            ['value' => 'ffffffffffff5fffcfffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\x5f\xff\xcf\xff\xff\xff\xff\xff\xff\xff"],
            ['value' => 'ffffffff-ffff-6fff-cfff-ffffffffffff'],
            ['value' => 'ffffffffffff6fffcfffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\x6f\xff\xcf\xff\xff\xff\xff\xff\xff\xff"],
            ['value' => 'ffffffff-ffff-7fff-cfff-ffffffffffff'],
            ['value' => 'ffffffffffff7fffcfffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\x7f\xff\xcf\xff\xff\xff\xff\xff\xff\xff"],
            ['value' => 'ffffffff-ffff-8fff-cfff-ffffffffffff'],
            ['value' => 'ffffffffffff8fffcfffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\x8f\xff\xcf\xff\xff\xff\xff\xff\xff\xff"],
        ];
    }

    public function testSerializeForString(): void
    {
        $expected =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV4":1:{s:4:"uuid";s:36:"27433d43-011d-4a6a-9161-1550863792c9";}';
        $serialized = serialize($this->uuidWithString);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForHex(): void
    {
        $expected =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV4":1:{s:4:"uuid";s:32:"27433d43011d4a6a91611550863792c9";}';
        $serialized = serialize($this->uuidWithHex);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForBytes(): void
    {
        $expected =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV4":1:{s:4:"uuid";s:16:'
            . "\"\x27\x43\x3d\x43\x01\x1d\x4a\x6a\x91\x61\x15\x50\x86\x37\x92\xc9\";}";
        $serialized = serialize($this->uuidWithBytes);

        $this->assertSame($expected, $serialized);
    }

    public function testCastsToString(): void
    {
        $this->assertSame(self::UUID_V4_STRING, (string) $this->uuidWithString);
        $this->assertSame(self::UUID_V4_STRING, (string) $this->uuidWithHex);
        $this->assertSame(self::UUID_V4_STRING, (string) $this->uuidWithBytes);
    }

    public function testUnserializeForString(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV4":1:{s:4:"uuid";s:36:"27433d43-011d-4a6a-9161-1550863792c9";}';
        $uuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\UuidV4::class, $uuid);
        $this->assertSame(self::UUID_V4_STRING, (string) $uuid);
    }

    public function testUnserializeForHex(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV4":1:{s:4:"uuid";s:32:"27433d43011d4a6a91611550863792c9";}';
        $uuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\UuidV4::class, $uuid);
        $this->assertSame(self::UUID_V4_STRING, (string) $uuid);
    }

    public function testUnserializeForBytes(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV4":1:{s:4:"uuid";s:16:'
            . "\"\x27\x43\x3d\x43\x01\x1d\x4a\x6a\x91\x61\x15\x50\x86\x37\x92\xc9\";}";
        $uuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\UuidV4::class, $uuid);
        $this->assertSame(self::UUID_V4_STRING, (string) $uuid);
    }

    public function testUnserializeFailsWhenUuidIsAnEmptyString(): void
    {
        $serialized = 'O:29:"Ramsey\\Identifier\\Uuid\\UuidV4":1:{s:4:"uuid";s:0:"";}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid version 4 UUID: ""');

        unserialize($serialized);
    }

    public function testUnserializeFailsForInvalidVersionUuid(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV4":1:{s:4:"uuid";s:36:"27433d43-011d-9a6a-9161-1550863792c9";}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid version 4 UUID: "27433d43-011d-9a6a-9161-1550863792c9"');

        unserialize($serialized);
    }

    #[DataProvider('compareToProvider')]
    public function testCompareTo(mixed $other, Comparison $comparison): void
    {
        switch ($comparison) {
            case Comparison::Equal:
                $this->assertSame(0, $this->uuidWithString->compareTo($other));
                $this->assertSame(0, $this->uuidWithHex->compareTo($other));
                $this->assertSame(0, $this->uuidWithBytes->compareTo($other));

                break;
            case Comparison::GreaterThan:
                $this->assertGreaterThan(0, $this->uuidWithString->compareTo($other));
                $this->assertGreaterThan(0, $this->uuidWithHex->compareTo($other));
                $this->assertGreaterThan(0, $this->uuidWithBytes->compareTo($other));

                break;
            case Comparison::LessThan:
                $this->assertLessThan(0, $this->uuidWithString->compareTo($other));
                $this->assertLessThan(0, $this->uuidWithHex->compareTo($other));
                $this->assertLessThan(0, $this->uuidWithBytes->compareTo($other));

                break;
            default:
                throw new Exception('Invalid comparison');
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
            'with same string UUID' => [self::UUID_V4_STRING, Comparison::Equal],
            'with same string UUID all caps' => [strtoupper(self::UUID_V4_STRING), Comparison::Equal],
            'with same hex UUID' => [self::UUID_V4_HEX, Comparison::Equal],
            'with same hex UUID all caps' => [strtoupper(self::UUID_V4_HEX), Comparison::Equal],
            'with same bytes UUID' => [self::UUID_V4_BYTES, Comparison::Equal],
            'with string Max UUID' => ['ffffffff-ffff-ffff-ffff-ffffffffffff', Comparison::LessThan],
            'with string Max UUID all caps' => ['FFFFFFFF-FFFF-FFFF-FFFF-FFFFFFFFFFFF', Comparison::LessThan],
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
                new class (self::UUID_V4_BYTES) {
                    public function __construct(private readonly string $uuidBytes)
                    {
                    }

                    public function __toString(): string
                    {
                        return $this->uuidBytes;
                    }
                },
                Comparison::Equal,
            ],
            'with NilUuid' => [new Uuid\NilUuid(), Comparison::GreaterThan],
            'with UuidV4 from string' => [new Uuid\UuidV4(self::UUID_V4_STRING), Comparison::Equal],
            'with UuidV4 from hex' => [new Uuid\UuidV4(self::UUID_V4_HEX), Comparison::Equal],
            'with UuidV4 from bytes' => [new Uuid\UuidV4(self::UUID_V4_BYTES), Comparison::Equal],
            'with MaxUuid' => [new Uuid\MaxUuid(), Comparison::LessThan],
            'with BinaryIdentifier class' => [new MockBinaryIdentifier(self::UUID_V4_BYTES), Comparison::Equal],
            'with Microsoft GUID' => [new Uuid\MicrosoftGuid(self::UUID_V4_STRING), Comparison::Equal],
            'with Ulid' => [new Ulid(self::UUID_V4_BYTES), Comparison::Equal],
        ];
    }

    public function testCompareToThrowsExceptionWhenNotComparable(): void
    {
        $this->expectException(NotComparable::class);
        $this->expectExceptionMessage('Comparison with values of type "array" is not supported');

        $this->uuidWithString->compareTo([]);
    }

    #[DataProvider('equalsProvider')]
    public function testEquals(mixed $other, Comparison $comparison): void
    {
        switch ($comparison) {
            case Comparison::Equal:
                $this->assertTrue($this->uuidWithString->equals($other));
                $this->assertTrue($this->uuidWithHex->equals($other));
                $this->assertTrue($this->uuidWithBytes->equals($other));

                break;
            case Comparison::NotEqual:
                $this->assertFalse($this->uuidWithString->equals($other));
                $this->assertFalse($this->uuidWithHex->equals($other));
                $this->assertFalse($this->uuidWithBytes->equals($other));

                break;
            default:
                throw new Exception('Invalid comparison');
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
            'with same string UUID' => [self::UUID_V4_STRING, Comparison::Equal],
            'with same string UUID all caps' => [strtoupper(self::UUID_V4_STRING), Comparison::Equal],
            'with same hex UUID' => [self::UUID_V4_HEX, Comparison::Equal],
            'with same hex UUID all caps' => [strtoupper(self::UUID_V4_HEX), Comparison::Equal],
            'with same bytes UUID' => [self::UUID_V4_BYTES, Comparison::Equal],
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
                new class (self::UUID_V4_BYTES) {
                    public function __construct(private readonly string $uuidBytes)
                    {
                    }

                    public function __toString(): string
                    {
                        return $this->uuidBytes;
                    }
                },
                Comparison::Equal,
            ],
            'with NilUuid' => [new Uuid\NilUuid(), Comparison::NotEqual],
            'with UuidV4 from string' => [new Uuid\UuidV4(self::UUID_V4_STRING), Comparison::Equal],
            'with UuidV4 from hex' => [new Uuid\UuidV4(self::UUID_V4_HEX), Comparison::Equal],
            'with UuidV4 from bytes' => [new Uuid\UuidV4(self::UUID_V4_BYTES), Comparison::Equal],
            'with MaxUuid' => [new Uuid\MaxUuid(), Comparison::NotEqual],
            'with array' => [[], Comparison::NotEqual],
            'with BinaryIdentifier class' => [new MockBinaryIdentifier(self::UUID_V4_BYTES), Comparison::Equal],
            'with Microsoft GUID' => [new Uuid\MicrosoftGuid(self::UUID_V4_STRING), Comparison::Equal],
            'with Ulid' => [new Ulid(self::UUID_V4_BYTES), Comparison::Equal],
        ];
    }

    public function testGetVariant(): void
    {
        $this->assertSame(Variant::Rfc, $this->uuidWithString->getVariant());
        $this->assertSame(Variant::Rfc, $this->uuidWithHex->getVariant());
        $this->assertSame(Variant::Rfc, $this->uuidWithBytes->getVariant());
    }

    public function testGetVersion(): void
    {
        $this->assertSame(Version::Random, $this->uuidWithString->getVersion());
        $this->assertSame(Version::Random, $this->uuidWithHex->getVersion());
        $this->assertSame(Version::Random, $this->uuidWithBytes->getVersion());
    }

    public function testJsonSerialize(): void
    {
        $this->assertSame('"' . self::UUID_V4_STRING . '"', json_encode($this->uuidWithString));
        $this->assertSame('"' . self::UUID_V4_STRING . '"', json_encode($this->uuidWithHex));
        $this->assertSame('"' . self::UUID_V4_STRING . '"', json_encode($this->uuidWithBytes));
    }

    public function testToString(): void
    {
        $this->assertSame(self::UUID_V4_STRING, $this->uuidWithString->toString());
        $this->assertSame(self::UUID_V4_STRING, $this->uuidWithHex->toString());
        $this->assertSame(self::UUID_V4_STRING, $this->uuidWithBytes->toString());
    }

    public function testToBytes(): void
    {
        $this->assertSame(self::UUID_V4_BYTES, $this->uuidWithString->toBytes());
        $this->assertSame(self::UUID_V4_BYTES, $this->uuidWithHex->toBytes());
        $this->assertSame(self::UUID_V4_BYTES, $this->uuidWithBytes->toBytes());
    }

    public function testToHexadecimal(): void
    {
        $this->assertSame(self::UUID_V4_HEX, $this->uuidWithString->toHexadecimal());
        $this->assertSame(self::UUID_V4_HEX, $this->uuidWithHex->toHexadecimal());
        $this->assertSame(self::UUID_V4_HEX, $this->uuidWithBytes->toHexadecimal());
    }

    public function testToInteger(): void
    {
        $int = '52189018260751158981506949280347689673';

        $this->assertSame($int, $this->uuidWithString->toInteger());
        $this->assertSame($int, $this->uuidWithHex->toInteger());
        $this->assertSame($int, $this->uuidWithBytes->toInteger());
    }

    public function testToUrn(): void
    {
        $this->assertSame('urn:uuid:' . self::UUID_V4_STRING, $this->uuidWithString->toUrn());
        $this->assertSame('urn:uuid:' . self::UUID_V4_STRING, $this->uuidWithHex->toUrn());
        $this->assertSame('urn:uuid:' . self::UUID_V4_STRING, $this->uuidWithBytes->toUrn());
    }

    /**
     * @param non-empty-string $value
     */
    #[DataProvider('valuesForLowercaseConversionTestProvider')]
    public function testLowercaseConversion(string $value, string $expected): void
    {
        $uuid = new Uuid\UuidV4($value);

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
                'value' => '27433D43-011D-4A6A-9161-1550863792C9',
                'expected' => '27433d43-011d-4a6a-9161-1550863792c9',
            ],
            [
                'value' => '27433D43011D4A6A91611550863792C9',
                'expected' => '27433d43-011d-4a6a-9161-1550863792c9',
            ],
            [
                'value' => "\x27\x43\x3D\x43\x01\x1D\x4A\x6A\x91\x61\x15\x50\x86\x37\x92\xC9",
                'expected' => '27433d43-011d-4a6a-9161-1550863792c9',
            ],
        ];
    }
}
