<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid;

use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Exception\NotComparable;
use Ramsey\Identifier\Uuid;
use Ramsey\Identifier\Uuid\Variant;
use Ramsey\Identifier\Uuid\Version;
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
     * @dataProvider invalidUuidsProvider
     */
    public function testConstructorThrowsExceptionForInvalidUuid(string $value): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(sprintf('Invalid version 4 UUID: "%s"', $value));

        new Uuid\UuidV4($value);
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

    /**
     * @dataProvider compareToProvider
     */
    public function testCompareTo(mixed $other, int $expected): void
    {
        $this->assertSame($expected, $this->uuidWithString->compareTo($other));
        $this->assertSame($expected, $this->uuidWithHex->compareTo($other));
        $this->assertSame($expected, $this->uuidWithBytes->compareTo($other));
    }

    /**
     * @return array<string, array{mixed, int}>
     */
    public function compareToProvider(): array
    {
        return [
            'with null' => [null, 36],
            'with int' => [123, 1],
            'with float' => [123.456, 1],
            'with string' => ['foobar', -52],
            'with string Nil UUID' => ['00000000-0000-0000-0000-000000000000', 2],
            'with same string UUID' => [self::UUID_V4_STRING, 0],
            'with same string UUID all caps' => [strtoupper(self::UUID_V4_STRING), 0],
            'with same hex UUID' => [self::UUID_V4_HEX, 0],
            'with same hex UUID all caps' => [strtoupper(self::UUID_V4_HEX), 0],
            'with same bytes UUID' => [self::UUID_V4_BYTES, 0],
            'with string Max UUID' => ['ffffffff-ffff-ffff-ffff-ffffffffffff', -52],
            'with string Max UUID all caps' => ['FFFFFFFF-FFFF-FFFF-FFFF-FFFFFFFFFFFF', -52],
            'with bool true' => [true, 1],
            'with bool false' => [false, 36],
            'with Stringable class' => [
                new class {
                    public function __toString(): string
                    {
                        return 'foobar';
                    }
                },
                -52,
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
                0,
            ],
            'with NilUuid' => [new Uuid\NilUuid(), 2],
            'with UuidV4 from string' => [new Uuid\UuidV4(self::UUID_V4_STRING), 0],
            'with UuidV4 from hex' => [new Uuid\UuidV4(self::UUID_V4_HEX), 0],
            'with UuidV4 from bytes' => [new Uuid\UuidV4(self::UUID_V4_BYTES), 0],
            'with MaxUuid' => [new Uuid\MaxUuid(), -52],
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
    public function testEquals(mixed $other, bool $expected): void
    {
        $this->assertSame($expected, $this->uuidWithString->equals($other));
        $this->assertSame($expected, $this->uuidWithHex->equals($other));
        $this->assertSame($expected, $this->uuidWithBytes->equals($other));
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
            'with string Nil UUID' => ['00000000-0000-0000-0000-000000000000', false],
            'with same string UUID' => [self::UUID_V4_STRING, true],
            'with same string UUID all caps' => [strtoupper(self::UUID_V4_STRING), true],
            'with same hex UUID' => [self::UUID_V4_HEX, true],
            'with same hex UUID all caps' => [strtoupper(self::UUID_V4_HEX), true],
            'with same bytes UUID' => [self::UUID_V4_BYTES, true],
            'with string Max UUID' => ['ffffffff-ffff-ffff-ffff-ffffffffffff', false],
            'with string Max UUID all caps' => ['FFFFFFFF-FFFF-FFFF-FFFF-FFFFFFFFFFFF', false],
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
                new class (self::UUID_V4_BYTES) {
                    public function __construct(private readonly string $uuidBytes)
                    {
                    }

                    public function __toString(): string
                    {
                        return $this->uuidBytes;
                    }
                },
                true,
            ],
            'with NilUuid' => [new Uuid\NilUuid(), false],
            'with UuidV4 from string' => [new Uuid\UuidV4(self::UUID_V4_STRING), true],
            'with UuidV4 from hex' => [new Uuid\UuidV4(self::UUID_V4_HEX), true],
            'with UuidV4 from bytes' => [new Uuid\UuidV4(self::UUID_V4_BYTES), true],
            'with MaxUuid' => [new Uuid\MaxUuid(), false],
            'with array' => [[], false],
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
     * @dataProvider valuesForLowercaseConversionTestProvider
     */
    public function testLowercaseConversion(string $value, string $expected): void
    {
        $uuid = new Uuid\UuidV4($value);

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
