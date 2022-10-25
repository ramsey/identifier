<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid;

use DateTimeImmutable;
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

class UuidV1Test extends TestCase
{
    private const UUID_V1_STRING = '27433d43-011d-1a6a-9161-1550863792c9';
    private const UUID_V1_HEX = '27433d43011d1a6a91611550863792c9';
    private const UUID_V1_BYTES = "\x27\x43\x3d\x43\x01\x1d\x1a\x6a\x91\x61\x15\x50\x86\x37\x92\xc9";

    private Uuid\UuidV1 $uuidWithString;
    private Uuid\UuidV1 $uuidWithHex;
    private Uuid\UuidV1 $uuidWithBytes;

    protected function setUp(): void
    {
        $this->uuidWithString = new Uuid\UuidV1(self::UUID_V1_STRING);
        $this->uuidWithHex = new Uuid\UuidV1(self::UUID_V1_HEX);
        $this->uuidWithBytes = new Uuid\UuidV1(self::UUID_V1_BYTES);
    }

    /**
     * @dataProvider invalidUuidsProvider
     */
    public function testConstructorThrowsExceptionForInvalidUuid(string $value): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(sprintf('Invalid version 1 UUID: "%s"', $value));

        new Uuid\UuidV1($value);
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

            // Valid Nil UUID:
            ['value' => '00000000-0000-0000-0000-000000000000'],
            ['value' => '00000000000000000000000000000000'],
            ['value' => "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"],

            // Valid Max UUID:
            ['value' => 'ffffffff-ffff-ffff-ffff-ffffffffffff'],
            ['value' => 'ffffffffffffffffffffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff"],

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
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV1":1:{s:4:"uuid";s:36:"27433d43-011d-1a6a-9161-1550863792c9";}';
        $serialized = serialize($this->uuidWithString);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForHex(): void
    {
        $expected =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV1":1:{s:4:"uuid";s:32:"27433d43011d1a6a91611550863792c9";}';
        $serialized = serialize($this->uuidWithHex);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForBytes(): void
    {
        $expected =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV1":1:{s:4:"uuid";s:16:'
            . "\"\x27\x43\x3d\x43\x01\x1d\x1a\x6a\x91\x61\x15\x50\x86\x37\x92\xc9\";}";
        $serialized = serialize($this->uuidWithBytes);

        $this->assertSame($expected, $serialized);
    }

    public function testCastsToString(): void
    {
        $this->assertSame(self::UUID_V1_STRING, (string) $this->uuidWithString);
        $this->assertSame(self::UUID_V1_STRING, (string) $this->uuidWithHex);
        $this->assertSame(self::UUID_V1_STRING, (string) $this->uuidWithBytes);
    }

    public function testUnserializeForString(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV1":1:{s:4:"uuid";s:36:"27433d43-011d-1a6a-9161-1550863792c9";}';
        $uuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\UuidV1::class, $uuid);
        $this->assertSame(self::UUID_V1_STRING, (string) $uuid);
    }

    public function testUnserializeForHex(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV1":1:{s:4:"uuid";s:32:"27433d43011d1a6a91611550863792c9";}';
        $uuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\UuidV1::class, $uuid);
        $this->assertSame(self::UUID_V1_STRING, (string) $uuid);
    }

    public function testUnserializeForBytes(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV1":1:{s:4:"uuid";s:16:'
            . "\"\x27\x43\x3d\x43\x01\x1d\x1a\x6a\x91\x61\x15\x50\x86\x37\x92\xc9\";}";
        $uuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\UuidV1::class, $uuid);
        $this->assertSame(self::UUID_V1_STRING, (string) $uuid);
    }

    public function testUnserializeFailsWhenUuidIsAnEmptyString(): void
    {
        $serialized = 'O:29:"Ramsey\\Identifier\\Uuid\\UuidV1":1:{s:4:"uuid";s:0:"";}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid version 1 UUID: ""');

        unserialize($serialized);
    }

    public function testUnserializeFailsForInvalidVersionUuid(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV1":1:{s:4:"uuid";s:36:"27433d43-011d-9a6a-9161-1550863792c9";}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid version 1 UUID: "27433d43-011d-9a6a-9161-1550863792c9"');

        unserialize($serialized);
    }

    /**
     * @dataProvider compareToProvider
     */
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
            'with int' => [123, Comparison::GreaterThan],
            'with float' => [123.456, Comparison::GreaterThan],
            'with string' => ['foobar', Comparison::LessThan],
            'with string Nil UUID' => ['00000000-0000-0000-0000-000000000000', Comparison::GreaterThan],
            'with same string UUID' => [self::UUID_V1_STRING, Comparison::Equal],
            'with same string UUID all caps' => [strtoupper(self::UUID_V1_STRING), Comparison::Equal],
            'with same hex UUID' => [self::UUID_V1_HEX, Comparison::Equal],
            'with same hex UUID all caps' => [strtoupper(self::UUID_V1_HEX), Comparison::Equal],
            'with same bytes UUID' => [self::UUID_V1_BYTES, Comparison::Equal],
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
                new class (self::UUID_V1_BYTES) {
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
            'with UuidV1 from string' => [new Uuid\UuidV1(self::UUID_V1_STRING), Comparison::Equal],
            'with UuidV1 from hex' => [new Uuid\UuidV1(self::UUID_V1_HEX), Comparison::Equal],
            'with UuidV1 from bytes' => [new Uuid\UuidV1(self::UUID_V1_BYTES), Comparison::Equal],
            'with MaxUuid' => [new Uuid\MaxUuid(), Comparison::LessThan],
            'with BinaryIdentifier class' => [new MockBinaryIdentifier(self::UUID_V1_BYTES), Comparison::Equal],
            'with Microsoft GUID' => [new Uuid\MicrosoftGuid(self::UUID_V1_STRING), Comparison::Equal],
            'with Ulid' => [new Ulid(self::UUID_V1_BYTES), Comparison::Equal],
        ];
    }

    public function testCompareToThrowsExceptionWhenNotComparable(): void
    {
        $this->expectException(NotComparable::class);
        $this->expectExceptionMessage('Comparison with values of type "array" is not supported');

        $this->uuidWithString->compareTo([]);
    }

    /**
     * @dataProvider equalsProvider
     */
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
            'with string Nil UUID' => ['00000000-0000-0000-0000-000000000000', Comparison::NotEqual],
            'with same string UUID' => [self::UUID_V1_STRING, Comparison::Equal],
            'with same string UUID all caps' => [strtoupper(self::UUID_V1_STRING), Comparison::Equal],
            'with same hex UUID' => [self::UUID_V1_HEX, Comparison::Equal],
            'with same hex UUID all caps' => [strtoupper(self::UUID_V1_HEX), Comparison::Equal],
            'with same bytes UUID' => [self::UUID_V1_BYTES, Comparison::Equal],
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
                new class (self::UUID_V1_BYTES) {
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
            'with UuidV1 from string' => [new Uuid\UuidV1(self::UUID_V1_STRING), Comparison::Equal],
            'with UuidV1 from hex' => [new Uuid\UuidV1(self::UUID_V1_HEX), Comparison::Equal],
            'with UuidV1 from bytes' => [new Uuid\UuidV1(self::UUID_V1_BYTES), Comparison::Equal],
            'with MaxUuid' => [new Uuid\MaxUuid(), Comparison::NotEqual],
            'with array' => [[], Comparison::NotEqual],
            'with BinaryIdentifier class' => [new MockBinaryIdentifier(self::UUID_V1_BYTES), Comparison::Equal],
            'with Microsoft GUID' => [new Uuid\MicrosoftGuid(self::UUID_V1_STRING), Comparison::Equal],
            'with Ulid' => [new Ulid(self::UUID_V1_BYTES), Comparison::Equal],
        ];
    }

    public function testGetVariant(): void
    {
        $this->assertSame(Variant::Rfc4122, $this->uuidWithString->getVariant());
        $this->assertSame(Variant::Rfc4122, $this->uuidWithHex->getVariant());
        $this->assertSame(Variant::Rfc4122, $this->uuidWithBytes->getVariant());
    }

    public function testGetVersion(): void
    {
        $this->assertSame(Version::GregorianTime, $this->uuidWithString->getVersion());
        $this->assertSame(Version::GregorianTime, $this->uuidWithHex->getVersion());
        $this->assertSame(Version::GregorianTime, $this->uuidWithBytes->getVersion());
    }

    public function testJsonSerialize(): void
    {
        $this->assertSame('"' . self::UUID_V1_STRING . '"', json_encode($this->uuidWithString));
        $this->assertSame('"' . self::UUID_V1_STRING . '"', json_encode($this->uuidWithHex));
        $this->assertSame('"' . self::UUID_V1_STRING . '"', json_encode($this->uuidWithBytes));
    }

    public function testToString(): void
    {
        $this->assertSame(self::UUID_V1_STRING, $this->uuidWithString->toString());
        $this->assertSame(self::UUID_V1_STRING, $this->uuidWithHex->toString());
        $this->assertSame(self::UUID_V1_STRING, $this->uuidWithBytes->toString());
    }

    public function testToBytes(): void
    {
        $bytes = "\x27\x43\x3d\x43\x01\x1d\x1a\x6a\x91\x61\x15\x50\x86\x37\x92\xc9";

        $this->assertSame($bytes, $this->uuidWithString->toBytes());
        $this->assertSame($bytes, $this->uuidWithHex->toBytes());
        $this->assertSame($bytes, $this->uuidWithBytes->toBytes());
    }

    public function testToHexadecimal(): void
    {
        $hex = '27433d43011d1a6a91611550863792c9';

        $this->assertSame($hex, $this->uuidWithString->toHexadecimal());
        $this->assertSame($hex, $this->uuidWithHex->toHexadecimal());
        $this->assertSame($hex, $this->uuidWithBytes->toHexadecimal());
    }

    public function testToInteger(): void
    {
        $int = '52189018260750932307915771537377432265';

        $this->assertSame($int, $this->uuidWithString->toInteger());
        $this->assertSame($int, $this->uuidWithHex->toInteger());
        $this->assertSame($int, $this->uuidWithBytes->toInteger());
    }

    public function testToUrn(): void
    {
        $this->assertSame('urn:uuid:' . self::UUID_V1_STRING, $this->uuidWithString->toUrn());
        $this->assertSame('urn:uuid:' . self::UUID_V1_STRING, $this->uuidWithHex->toUrn());
        $this->assertSame('urn:uuid:' . self::UUID_V1_STRING, $this->uuidWithBytes->toUrn());
    }

    public function testGetDateTimeFromStringUuid(): void
    {
        $dateTime = $this->uuidWithString->getDateTime();

        $this->assertInstanceOf(DateTimeImmutable::class, $dateTime);
        $this->assertSame('3960-10-02T03:47:44+00:00', $dateTime->format('c'));
    }

    public function testGetDateTimeFromHexUuid(): void
    {
        $dateTime = $this->uuidWithHex->getDateTime();

        $this->assertInstanceOf(DateTimeImmutable::class, $dateTime);
        $this->assertSame('3960-10-02T03:47:44+00:00', $dateTime->format('c'));
    }

    public function testGetDateTimeFromBytesUuid(): void
    {
        $dateTime = $this->uuidWithBytes->getDateTime();

        $this->assertInstanceOf(DateTimeImmutable::class, $dateTime);
        $this->assertSame('3960-10-02T03:47:44+00:00', $dateTime->format('c'));
    }

    public function testGetNode(): void
    {
        $this->assertSame('1550863792c9', $this->uuidWithString->getNode());
        $this->assertSame('1550863792c9', $this->uuidWithHex->getNode());
        $this->assertSame('1550863792c9', $this->uuidWithBytes->getNode());
    }

    /**
     * @dataProvider valuesForLowercaseConversionTestProvider
     */
    public function testLowercaseConversion(string $value, string $expected): void
    {
        $uuid = new Uuid\UuidV1($value);

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
                'value' => '27433D43-011D-1A6A-9161-1550863792C9',
                'expected' => '27433d43-011d-1a6a-9161-1550863792c9',
            ],
            [
                'value' => '27433D43011D1A6A91611550863792C9',
                'expected' => '27433d43-011d-1a6a-9161-1550863792c9',
            ],
            [
                'value' => "\x27\x43\x3D\x43\x01\x1D\x1A\x6A\x91\x61\x15\x50\x86\x37\x92\xC9",
                'expected' => '27433d43-011d-1a6a-9161-1550863792c9',
            ],
        ];
    }
}
